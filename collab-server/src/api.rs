use axum::extract::State;
use axum::http::{HeaderMap, StatusCode};
use axum::response::IntoResponse;
use axum::Json;

use crate::state::AppState;

pub async fn health() -> impl IntoResponse {
    Json(serde_json::json!({
        "status": "ok",
        "version": env!("CARGO_PKG_VERSION"),
    }))
}

pub async fn rooms(
    headers: HeaderMap,
    State(state): State<AppState>,
) -> Result<impl IntoResponse, StatusCode> {
    let secret = headers
        .get("x-collab-secret")
        .and_then(|v| v.to_str().ok())
        .ok_or(StatusCode::FORBIDDEN)?;

    if secret != state.config.collab_secret {
        return Err(StatusCode::FORBIDDEN);
    }

    let note_ids = state.rooms.note_ids();
    Ok(Json(serde_json::json!({ "rooms": note_ids })))
}
