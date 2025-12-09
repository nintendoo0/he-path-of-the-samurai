use std::env;

#[derive(Clone, Debug)]
pub struct Config {
    pub database_url: String,
    pub nasa_api_url: String,
    pub nasa_api_key: String,
    pub where_iss_url: String,
    pub fetch_every_seconds: u64,
    pub iss_every_seconds: u64,
    pub apod_every_seconds: u64,
    pub neo_every_seconds: u64,
    pub donki_every_seconds: u64,
    pub spacex_every_seconds: u64,
    pub osdr_list_limit: i64,
}

impl Config {
    pub fn from_env() -> anyhow::Result<Self> {
        dotenvy::dotenv().ok();

        let database_url = env::var("DATABASE_URL")
            .map_err(|_| anyhow::anyhow!("DATABASE_URL is required"))?;

        let nasa_api_url = env::var("NASA_API_URL")
            .unwrap_or_else(|_| "https://visualization.osdr.nasa.gov/biodata/api/v2/datasets/?format=json".to_string());

        let nasa_api_key = env::var("NASA_API_KEY").unwrap_or_default();

        let where_iss_url = env::var("WHERE_ISS_URL")
            .unwrap_or_else(|_| "https://api.wheretheiss.at/v1/satellites/25544".to_string());

        Ok(Self {
            database_url,
            nasa_api_url,
            nasa_api_key,
            where_iss_url,
            fetch_every_seconds: env_u64("FETCH_EVERY_SECONDS", 600),
            iss_every_seconds: env_u64("ISS_EVERY_SECONDS", 120),
            apod_every_seconds: env_u64("APOD_EVERY_SECONDS", 43200),
            neo_every_seconds: env_u64("NEO_EVERY_SECONDS", 7200),
            donki_every_seconds: env_u64("DONKI_EVERY_SECONDS", 3600),
            spacex_every_seconds: env_u64("SPACEX_EVERY_SECONDS", 3600),
            osdr_list_limit: env_i64("OSDR_LIST_LIMIT", 20),
        })
    }
}

fn env_u64(k: &str, default: u64) -> u64 {
    env::var(k).ok().and_then(|s| s.parse().ok()).unwrap_or(default)
}

fn env_i64(k: &str, default: i64) -> i64 {
    env::var(k).ok().and_then(|s| s.parse().ok()).unwrap_or(default)
}
