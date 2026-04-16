use axum::http::StatusCode;
use axum::response::{IntoResponse, Response};

#[derive(Debug, thiserror::Error)]
pub enum AppError {
    #[error("invalid or expired token")]
    InvalidToken,

    #[error("forbidden: {0}")]
    Forbidden(&'static str),

    #[error("note not found")]
    NoteNotFound,

    #[error("upstream error: {0}")]
    Upstream(#[from] anyhow::Error),

    #[error("internal error: {0}")]
    Internal(String),
}

impl IntoResponse for AppError {
    fn into_response(self) -> Response {
        let (status, msg) = match &self {
            AppError::InvalidToken => (StatusCode::UNAUTHORIZED, self.to_string()),
            AppError::Forbidden(m) => (StatusCode::FORBIDDEN, m.to_string()),
            AppError::NoteNotFound => (StatusCode::NOT_FOUND, self.to_string()),
            AppError::Upstream(e) => {
                tracing::error!("upstream: {e}");
                (StatusCode::BAD_GATEWAY, "upstream error".into())
            }
            AppError::Internal(e) => {
                tracing::error!("internal: {e}");
                (StatusCode::INTERNAL_SERVER_ERROR, "internal error".into())
            }
        };

        (status, serde_json::json!({ "error": msg }).to_string()).into_response()
    }
}
