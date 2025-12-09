use axum::{
    extract::{Path, Query, State},
    Json,
};
use chrono::Utc;
use serde_json::Value;
use std::collections::HashMap;

use crate::domain::Health;
use crate::error::ApiError;
use crate::services::{IssService, OsdrService, SpaceService};

#[derive(Clone)]
pub struct AppState {
    pub iss_service: IssService,
    pub osdr_service: OsdrService,
    pub space_service: SpaceService,
}

// Health check
pub async fn health() -> Json<Health> {
    Json(Health {
        status: "ok",
        now: Utc::now(),
    })
}

// ISS handlers
pub async fn iss_last(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let position = state.iss_service.get_last().await?;

    if let Some(pos) = position {
        Ok(Json(serde_json::json!({
            "id": pos.id,
            "fetched_at": pos.fetched_at,
            "source_url": pos.source_url,
            "payload": pos.payload
        })))
    } else {
        Ok(Json(serde_json::json!({"message": "no data"})))
    }
}

pub async fn iss_trigger(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    state.iss_service.fetch_and_store().await?;
    iss_last(State(state)).await
}

pub async fn iss_trend(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let trend = state.iss_service.get_trend().await?;
    Ok(Json(serde_json::to_value(trend).unwrap()))
}

// OSDR handlers
pub async fn osdr_sync(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let written = state.osdr_service.sync().await?;
    Ok(Json(serde_json::json!({ "written": written })))
}

pub async fn osdr_list(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let limit = state.osdr_service.list(20).await?;
    Ok(Json(serde_json::json!({ "items": limit })))
}

// Space cache handlers
pub async fn space_latest(
    Path(src): Path<String>,
    State(state): State<AppState>,
) -> Result<Json<Value>, ApiError> {
    let data = state.space_service.get_latest(&src).await?;

    if let Some(d) = data {
        Ok(Json(serde_json::json!({
            "source": d.source,
            "fetched_at": d.fetched_at,
            "payload": d.payload
        })))
    } else {
        Ok(Json(serde_json::json!({
            "source": src,
            "message": "no data"
        })))
    }
}

pub async fn space_refresh(
    Query(params): Query<HashMap<String, String>>,
    State(state): State<AppState>,
) -> Result<Json<Value>, ApiError> {
    let list = params
        .get("src")
        .cloned()
        .unwrap_or_else(|| "apod,neo,flr,cme,spacex".to_string());

    let sources: Vec<&str> = list.split(',').map(|s| s.trim()).collect();
    let done = state.space_service.refresh(sources).await?;

    Ok(Json(serde_json::json!({ "refreshed": done })))
}

pub async fn space_summary(State(state): State<AppState>) -> Result<Json<Value>, ApiError> {
    let summary = state.space_service.get_summary().await?;
    Ok(Json(summary))
}
