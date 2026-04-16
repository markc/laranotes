use std::sync::Arc;

use axum::extract::ws::{WebSocket, WebSocketUpgrade};
use axum::extract::{Path, Query, State};
use axum::response::Response;
use futures_util::StreamExt;
use serde::Deserialize;
use tokio::sync::Mutex;
use yrs::sync::{Awareness, Error, Message, Protocol};
use yrs::Update;
use yrs_axum::broadcast::BroadcastGroup;
use yrs_axum::ws::{AxumSink, AxumStream};

use crate::auth::{self, Claims};
use crate::error::AppError;
use crate::state::AppState;

struct ReadOnlyProtocol;

impl Protocol for ReadOnlyProtocol {
    fn handle_sync_step2(
        &self,
        _awareness: &mut Awareness,
        _update: Update,
    ) -> Result<Option<Message>, Error> {
        Ok(None)
    }

    fn handle_update(
        &self,
        _awareness: &mut Awareness,
        _update: Update,
    ) -> Result<Option<Message>, Error> {
        Ok(None)
    }
}

#[derive(Deserialize)]
pub struct WsParams {
    pub token: String,
}

pub async fn ws_handler(
    ws: WebSocketUpgrade,
    Path(note_id): Path<i64>,
    Query(params): Query<WsParams>,
    State(state): State<AppState>,
) -> Result<Response, AppError> {
    let claims = auth::verify_token(&params.token, &state.config.token_secrets)?;

    if claims.note != note_id {
        return Err(AppError::Forbidden("token note mismatch"));
    }

    // Load or create room
    let room = match state.rooms.get(note_id) {
        Some(r) => r,
        None => {
            let body = state
                .laravel
                .load_body(note_id)
                .await
                .unwrap_or_else(|e| {
                    tracing::warn!("failed to load note {note_id}: {e}, starting empty");
                    String::new()
                });
            state.rooms.get_or_insert_with(note_id, &body)
        }
    };

    // Ensure a BroadcastGroup exists for this room
    let bcast = state.get_or_create_broadcast(note_id, &room).await;

    Ok(ws.on_upgrade(move |socket| {
        handle_peer(socket, room, bcast, claims, state)
    }))
}

async fn handle_peer(
    socket: WebSocket,
    room: Arc<crate::room::Room>,
    bcast: Arc<BroadcastGroup>,
    claims: Claims,
    state: AppState,
) {
    let count = room.add_peer();
    room.mark_dirty(claims.sub);
    tracing::info!(
        user = claims.sub,
        note = room.note_id,
        peers = count,
        "peer joined"
    );

    let (sink, stream) = socket.split();
    let sink = Arc::new(Mutex::new(AxumSink::from(sink)));
    let stream = AxumStream::from(stream);

    let sub = if claims.can_edit {
        bcast.subscribe(sink, stream)
    } else {
        tracing::info!(user = claims.sub, note = room.note_id, "read-only peer");
        bcast.subscribe_with(sink, stream, ReadOnlyProtocol)
    };
    if let Err(e) = sub.completed().await {
        tracing::debug!(note = room.note_id, "peer connection ended: {e}");
    }

    let remaining = room.remove_peer();
    tracing::info!(
        user = claims.sub,
        note = room.note_id,
        peers = remaining,
        "peer left"
    );

    if remaining == 0 {
        let body = room.get_body().await;
        let writer = room.last_writer.load(std::sync::atomic::Ordering::Acquire);
        match state.laravel.save_body(room.note_id, &body, writer).await {
            Ok(_) => {
                room.dirty.store(false, std::sync::atomic::Ordering::Release);
                tracing::info!(note = room.note_id, "saved on last disconnect");
            }
            Err(e) => tracing::error!(note = room.note_id, "save failed: {e}"),
        }
    }
}
