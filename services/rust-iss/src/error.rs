use axum::{
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use serde::Serialize;
use std::fmt;
use uuid::Uuid;

#[derive(Debug, Clone, Serialize)]
pub struct ErrorResponse {
    pub ok: bool,
    pub error: ErrorDetail,
}

#[derive(Debug, Clone, Serialize)]
pub struct ErrorDetail {
    pub code: String,
    pub message: String,
    pub trace_id: String,
}

#[derive(Debug)]
pub enum ApiError {
    DatabaseError(String),
    UpstreamError { code: String, message: String },
    ValidationError(String),
    NotFound(String),
    InternalError(String),
}

impl fmt::Display for ApiError {
    fn fmt(&self, f: &mut fmt::Formatter<'_>) -> fmt::Result {
        match self {
            ApiError::DatabaseError(msg) => write!(f, "Database error: {}", msg),
            ApiError::UpstreamError { code, message } => {
                write!(f, "Upstream error {}: {}", code, message)
            }
            ApiError::ValidationError(msg) => write!(f, "Validation error: {}", msg),
            ApiError::NotFound(msg) => write!(f, "Not found: {}", msg),
            ApiError::InternalError(msg) => write!(f, "Internal error: {}", msg),
        }
    }
}

impl IntoResponse for ApiError {
    fn into_response(self) -> Response {
        let trace_id = Uuid::new_v4().to_string();
        
        let (code, message) = match &self {
            ApiError::DatabaseError(msg) => ("DATABASE_ERROR".to_string(), msg.clone()),
            ApiError::UpstreamError { code, message } => (code.clone(), message.clone()),
            ApiError::ValidationError(msg) => ("VALIDATION_ERROR".to_string(), msg.clone()),
            ApiError::NotFound(msg) => ("NOT_FOUND".to_string(), msg.clone()),
            ApiError::InternalError(msg) => ("INTERNAL_ERROR".to_string(), msg.clone()),
        };

        tracing::error!("API Error [{}]: {} - {}", trace_id, code, message);

        let error_response = ErrorResponse {
            ok: false,
            error: ErrorDetail {
                code,
                message,
                trace_id,
            },
        };

        // Всегда возвращаем HTTP 200 с полем ok: false
        (StatusCode::OK, Json(error_response)).into_response()
    }
}

impl From<sqlx::Error> for ApiError {
    fn from(err: sqlx::Error) -> Self {
        ApiError::DatabaseError(err.to_string())
    }
}

impl From<reqwest::Error> for ApiError {
    fn from(err: reqwest::Error) -> Self {
        let code = if err.is_timeout() {
            "UPSTREAM_TIMEOUT".to_string()
        } else if err.is_connect() {
            "UPSTREAM_CONNECTION".to_string()
        } else if let Some(status) = err.status() {
            format!("UPSTREAM_{}", status.as_u16())
        } else {
            "UPSTREAM_ERROR".to_string()
        };

        ApiError::UpstreamError {
            code,
            message: err.to_string(),
        }
    }
}

impl From<anyhow::Error> for ApiError {
    fn from(err: anyhow::Error) -> Self {
        ApiError::InternalError(err.to_string())
    }
}
