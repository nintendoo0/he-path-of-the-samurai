mod clients;
mod config;
mod db;
mod domain;
mod error;
mod handlers;
mod repository;
mod scheduler;
mod services;
mod utils;
mod validators;

use axum::{routing::get, Router};
use sqlx::postgres::PgPoolOptions;
use tracing::info;
use tracing_subscriber::{EnvFilter, FmtSubscriber};
use tower::ServiceBuilder;
use tower_http::{trace::TraceLayer, cors::{CorsLayer, Any}};

use crate::clients::NasaClient;
use crate::config::Config;
use crate::db::init_db;
use crate::handlers::{
    health, iss_last, iss_trend, iss_history, iss_trigger, osdr_list, osdr_sync, space_latest, space_refresh,
    space_summary, AppState,
};
use crate::repository::{CacheRepository, IssRepository, OsdrRepository};
use crate::scheduler::spawn_background_tasks;
use crate::services::{IssService, OsdrService, SpaceService};

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    // Initialize tracing
    let subscriber = FmtSubscriber::builder()
        .with_env_filter(EnvFilter::from_default_env())
        .finish();
    tracing::subscriber::set_global_default(subscriber)?;

    info!("Starting rust_iss service...");

    // Load configuration
    let config = Config::from_env()?;
    info!("Configuration loaded successfully");

    // Connect to database
    let pool = PgPoolOptions::new()
        .max_connections(10)
        .connect(&config.database_url)
        .await?;
    info!("Database connected");

    // Initialize database schema
    init_db(&pool).await?;
    info!("Database schema initialized");

    // Create repositories
    let iss_repo = IssRepository::new(pool.clone());
    let osdr_repo = OsdrRepository::new(pool.clone());
    let cache_repo = CacheRepository::new(pool.clone());

    // Create clients
    let nasa_client = NasaClient::new(config.nasa_api_key.clone());

    // Create services
    let iss_service = IssService::new(iss_repo.clone(), config.where_iss_url.clone());
    let osdr_service = OsdrService::new(osdr_repo.clone(), nasa_client.clone(), config.nasa_api_url.clone());
    let space_service = SpaceService::new(
        cache_repo,
        iss_repo,
        osdr_repo,
        nasa_client,
    );

    // Spawn background tasks
    spawn_background_tasks(
        iss_service.clone(),
        osdr_service.clone(),
        space_service.clone(),
        config.iss_every_seconds,
        config.fetch_every_seconds,
        config.apod_every_seconds,
        config.neo_every_seconds,
        config.donki_every_seconds,
        config.spacex_every_seconds,
    );

    // Create app state
    let state = AppState {
        iss_service,
        osdr_service,
        space_service,
    };

    // Build router with middleware
    let cors = CorsLayer::new()
        .allow_origin(Any)
        .allow_methods(Any)
        .allow_headers(Any);

    let app = Router::new()
        // Health
        .route("/health", get(health))
        // ISS
        .route("/last", get(iss_last))
        .route("/fetch", get(iss_trigger))
        .route("/iss/trend", get(iss_trend))
        .route("/iss/history", get(iss_history))
        // OSDR
        .route("/osdr/sync", get(osdr_sync))
        .route("/osdr/list", get(osdr_list))
        // Space cache
        .route("/space/:src/latest", get(space_latest))
        .route("/space/refresh", get(space_refresh))
        .route("/space/summary", get(space_summary))
        .with_state(state)
        .layer(
            ServiceBuilder::new()
                .layer(TraceLayer::new_for_http())
                .layer(cors)
        );

    // Start server
    let listener = tokio::net::TcpListener::bind("0.0.0.0:3000").await?;
    info!("rust_iss listening on 0.0.0.0:3000");
    
    axum::serve(listener, app.into_make_service()).await?;

    Ok(())
}
