use serde_json::Value;
use tracing::info;

use crate::clients::{IssClient, NasaClient, SpaceXClient};
use crate::domain::{IssPosition, IssTrend, OsdrItem, SpaceData};
use crate::error::ApiError;
use crate::repository::{CacheRepository, IssRepository, OsdrRepository};
use crate::utils::{string_pick, time_pick};

#[derive(Clone)]
pub struct IssService {
    repository: IssRepository,
    client: IssClient,
    iss_url: String,
}

impl IssService {
    pub fn new(repository: IssRepository, iss_url: String) -> Self {
        Self {
            repository,
            client: IssClient::new(),
            iss_url,
        }
    }

    pub async fn fetch_and_store(&self) -> Result<(), ApiError> {
        info!("Fetching ISS position");
        let payload = self.client.fetch_position(&self.iss_url).await?;
        self.repository.insert(&self.iss_url, payload).await?;
        info!("ISS position stored");
        Ok(())
    }

    pub async fn get_last(&self) -> Result<Option<IssPosition>, ApiError> {
        self.repository.get_last().await
    }

    pub async fn get_trend(&self) -> Result<IssTrend, ApiError> {
        self.repository.get_trend().await
    }
}

#[derive(Clone)]
pub struct OsdrService {
    repository: OsdrRepository,
    client: NasaClient,
    osdr_url: String,
}

impl OsdrService {
    pub fn new(repository: OsdrRepository, client: NasaClient, osdr_url: String) -> Self {
        Self {
            repository,
            client,
            osdr_url,
        }
    }

    pub async fn sync(&self) -> Result<usize, ApiError> {
        info!("Syncing OSDR data");
        let json = self.client.fetch_osdr(&self.osdr_url).await?;

        let items = if let Some(a) = json.as_array() {
            a.clone()
        } else if let Some(v) = json.get("items").and_then(|x| x.as_array()) {
            v.clone()
        } else if let Some(v) = json.get("results").and_then(|x| x.as_array()) {
            v.clone()
        } else {
            vec![json.clone()]
        };

        let mut written = 0usize;
        for item in items {
            let dataset_id = string_pick(
                &item,
                &["dataset_id", "id", "uuid", "studyId", "accession", "osdr_id"],
            );
            let title = string_pick(&item, &["title", "name", "label"]);
            let status = string_pick(&item, &["status", "state", "lifecycle"]);
            let updated_at = time_pick(
                &item,
                &["updated", "updated_at", "modified", "lastUpdated", "timestamp"],
            );

            self.repository
                .upsert_item(dataset_id, title, status, updated_at, item)
                .await?;
            written += 1;
        }

        info!("OSDR sync completed: {} items written", written);
        Ok(written)
    }

    pub async fn list(&self, limit: i64) -> Result<Vec<OsdrItem>, ApiError> {
        self.repository.list(limit).await
    }

    pub async fn count(&self) -> Result<i64, ApiError> {
        self.repository.count().await
    }
}

#[derive(Clone)]
pub struct SpaceService {
    cache_repo: CacheRepository,
    iss_repo: IssRepository,
    osdr_repo: OsdrRepository,
    nasa_client: NasaClient,
    spacex_client: SpaceXClient,
}

impl SpaceService {
    pub fn new(
        cache_repo: CacheRepository,
        iss_repo: IssRepository,
        osdr_repo: OsdrRepository,
        nasa_client: NasaClient,
    ) -> Self {
        Self {
            cache_repo,
            iss_repo,
            osdr_repo,
            nasa_client,
            spacex_client: SpaceXClient::new(),
        }
    }

    pub async fn fetch_apod(&self) -> Result<(), ApiError> {
        info!("Fetching APOD");
        let payload = self.nasa_client.fetch_apod().await?;
        self.cache_repo.insert("apod", payload).await?;
        Ok(())
    }

    pub async fn fetch_neo(&self) -> Result<(), ApiError> {
        info!("Fetching NEO feed");
        let payload = self.nasa_client.fetch_neo_feed().await?;
        self.cache_repo.insert("neo", payload).await?;
        Ok(())
    }

    pub async fn fetch_donki_flr(&self) -> Result<(), ApiError> {
        info!("Fetching DONKI FLR");
        let payload = self.nasa_client.fetch_donki_flr().await?;
        self.cache_repo.insert("flr", payload).await?;
        Ok(())
    }

    pub async fn fetch_donki_cme(&self) -> Result<(), ApiError> {
        info!("Fetching DONKI CME");
        let payload = self.nasa_client.fetch_donki_cme().await?;
        self.cache_repo.insert("cme", payload).await?;
        Ok(())
    }

    pub async fn fetch_spacex(&self) -> Result<(), ApiError> {
        info!("Fetching SpaceX next launch");
        let payload = self.spacex_client.fetch_next_launch().await?;
        self.cache_repo.insert("spacex", payload).await?;
        Ok(())
    }

    pub async fn get_latest(&self, source: &str) -> Result<Option<SpaceData>, ApiError> {
        self.cache_repo.get_latest(source).await
    }

    pub async fn refresh(&self, sources: Vec<&str>) -> Result<Vec<String>, ApiError> {
        let mut done = Vec::new();
        for src in sources {
            let result = match src {
                "apod" => {
                    self.fetch_apod().await.ok();
                    Some("apod")
                }
                "neo" => {
                    self.fetch_neo().await.ok();
                    Some("neo")
                }
                "flr" => {
                    self.fetch_donki_flr().await.ok();
                    Some("flr")
                }
                "cme" => {
                    self.fetch_donki_cme().await.ok();
                    Some("cme")
                }
                "spacex" => {
                    self.fetch_spacex().await.ok();
                    Some("spacex")
                }
                _ => None,
            };

            if let Some(name) = result {
                done.push(name.to_string());
            }
        }
        Ok(done)
    }

    pub async fn get_summary(&self) -> Result<Value, ApiError> {
        let apod = self.get_latest("apod").await.ok().flatten();
        let neo = self.get_latest("neo").await.ok().flatten();
        let flr = self.get_latest("flr").await.ok().flatten();
        let cme = self.get_latest("cme").await.ok().flatten();
        let spacex = self.get_latest("spacex").await.ok().flatten();
        let iss_last = self.iss_repo.get_last().await.ok().flatten();
        let osdr_count = self.osdr_repo.count().await.unwrap_or(0);

        let to_json = |data: Option<SpaceData>| {
            data.map(|d| {
                serde_json::json!({
                    "at": d.fetched_at,
                    "payload": d.payload
                })
            })
            .unwrap_or(serde_json::json!({}))
        };

        let iss_json = iss_last
            .map(|iss| {
                serde_json::json!({
                    "at": iss.fetched_at,
                    "payload": iss.payload
                })
            })
            .unwrap_or(serde_json::json!({}));

        Ok(serde_json::json!({
            "apod": to_json(apod),
            "neo": to_json(neo),
            "flr": to_json(flr),
            "cme": to_json(cme),
            "spacex": to_json(spacex),
            "iss": iss_json,
            "osdr_count": osdr_count
        }))
    }
}
