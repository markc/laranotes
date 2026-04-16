mod api;
mod auth;
mod config;
mod error;
mod persistence;
mod room;
mod state;
mod ws;

use std::sync::atomic::Ordering;
use std::time::Duration;

use axum::routing::get;
use axum::Router;
use tokio::signal;
use tower_http::trace::TraceLayer;

use crate::config::Config;
use crate::persistence::LaravelClient;
use crate::state::AppState;

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    dotenvy::dotenv().ok();

    tracing_subscriber::fmt()
        .with_env_filter(
            tracing_subscriber::EnvFilter::try_from_default_env()
                .unwrap_or_else(|_| "laranotes_collab=info,tower_http=info".into()),
        )
        .init();

    let config = Config::from_env()?;
    let laravel = LaravelClient::new(
        config.laravel_base_url.clone(),
        config.collab_secret.clone(),
    );
    let state = AppState::new(config.clone(), laravel);

    tracing::info!(
        bind = %config.bind_addr,
        laravel = %config.laravel_base_url,
        "starting collab server"
    );

    // Background tasks
    tokio::spawn(periodic_save(state.clone()));
    tokio::spawn(idle_room_gc(state.clone()));

    let app = Router::new()
        .route("/health", get(api::health))
        .route("/rooms", get(api::rooms))
        .route("/ws/note/{note_id}", get(ws::ws_handler))
        .layer(TraceLayer::new_for_http())
        .with_state(state);

    let listener = tokio::net::TcpListener::bind(config.bind_addr).await?;
    tracing::info!("collab server running on {}", config.bind_addr);

    axum::serve(listener, app)
        .with_graceful_shutdown(shutdown_signal())
        .await?;

    tracing::info!("collab server stopped");
    Ok(())
}

async fn shutdown_signal() {
    let ctrl_c = async {
        signal::ctrl_c().await.expect("failed to install Ctrl+C handler");
    };

    #[cfg(unix)]
    let terminate = async {
        signal::unix::signal(signal::unix::SignalKind::terminate())
            .expect("failed to install SIGTERM handler")
            .recv()
            .await;
    };

    #[cfg(not(unix))]
    let terminate = std::future::pending::<()>();

    tokio::select! {
        _ = ctrl_c => tracing::info!("received Ctrl+C"),
        _ = terminate => tracing::info!("received SIGTERM"),
    }
}

async fn periodic_save(state: AppState) {
    let interval = Duration::from_secs(state.config.periodic_save_interval_secs);
    let mut tick = tokio::time::interval(interval);

    loop {
        tick.tick().await;

        for room in state.rooms.dirty_rooms() {
            let body = room.get_body().await;
            let writer = room.last_writer.load(Ordering::Acquire);

            match state.laravel.save_body(room.note_id, &body, writer).await {
                Ok(_) => {
                    room.dirty.store(false, Ordering::Release);
                    tracing::debug!(note = room.note_id, "periodic save ok");
                }
                Err(e) => {
                    tracing::warn!(note = room.note_id, "periodic save failed: {e}");
                }
            }
        }
    }
}

async fn idle_room_gc(state: AppState) {
    let ttl = Duration::from_secs(state.config.idle_room_ttl_secs);
    let mut tick = tokio::time::interval(Duration::from_secs(60));

    loop {
        tick.tick().await;

        for note_id in state.rooms.idle_rooms(ttl) {
            // Flush before removing
            if let Some(room) = state.rooms.get(note_id) {
                if room.dirty.load(Ordering::Acquire) {
                    let body = room.get_body().await;
                    let writer = room.last_writer.load(Ordering::Acquire);
                    let _ = state.laravel.save_body(note_id, &body, writer).await;
                }
            }
            state.rooms.remove(note_id);
            state.remove_broadcast(note_id).await;
            tracing::info!(note = note_id, "gc'd idle room");
        }
    }
}
