use reqwest::Client;
use serde_json::Value;
use std::time::Duration;
use tracing::debug;

use crate::error::ApiError;
use crate::utils::last_days;

#[derive(Clone)]
pub struct NasaClient {
    client: Client,
    api_key: String,
}

impl NasaClient {
    pub fn new(api_key: String) -> Self {
        let client = Client::builder()
            .timeout(Duration::from_secs(30))
            .user_agent("rust-iss/1.0")
            .build()
            .unwrap();

        Self { client, api_key }
    }

    pub async fn fetch_apod(&self) -> Result<Value, ApiError> {
        debug!("Fetching APOD from NASA");
        let url = "https://api.nasa.gov/planetary/apod";
        let mut req = self.client.get(url).query(&[("thumbs", "true")]);
        
        if !self.api_key.is_empty() {
            req = req.query(&[("api_key", &self.api_key)]);
        }
        
        let json: Value = req.send().await?.json().await?;
        Ok(json)
    }

    pub async fn fetch_neo_feed(&self) -> Result<Value, ApiError> {
        debug!("Fetching NEO feed from NASA");
        let today = chrono::Utc::now().date_naive();
        let start = today - chrono::Days::new(2);
        let url = "https://api.nasa.gov/neo/rest/v1/feed";
        
        let mut req = self.client.get(url).query(&[
            ("start_date", start.to_string()),
            ("end_date", today.to_string()),
        ]);
        
        if !self.api_key.is_empty() {
            req = req.query(&[("api_key", &self.api_key)]);
        }
        
        let json: Value = req.send().await?.json().await?;
        Ok(json)
    }

    pub async fn fetch_donki_flr(&self) -> Result<Value, ApiError> {
        debug!("Fetching DONKI FLR from NASA");
        let (from, to) = last_days(5);
        let url = "https://api.nasa.gov/DONKI/FLR";
        
        let mut req = self.client.get(url).query(&[
            ("startDate", from),
            ("endDate", to),
        ]);
        
        if !self.api_key.is_empty() {
            req = req.query(&[("api_key", &self.api_key)]);
        }
        
        let json: Value = req.send().await?.json().await?;
        Ok(json)
    }

    pub async fn fetch_donki_cme(&self) -> Result<Value, ApiError> {
        debug!("Fetching DONKI CME from NASA");
        let (from, to) = last_days(5);
        let url = "https://api.nasa.gov/DONKI/CME";
        
        let mut req = self.client.get(url).query(&[
            ("startDate", from),
            ("endDate", to),
        ]);
        
        if !self.api_key.is_empty() {
            req = req.query(&[("api_key", &self.api_key)]);
        }
        
        let json: Value = req.send().await?.json().await?;
        Ok(json)
    }

    pub async fn fetch_osdr(&self, url: &str) -> Result<Value, ApiError> {
        debug!("Fetching OSDR data from: {}", url);
        let resp = self.client.get(url).send().await?;
        
        if !resp.status().is_success() {
            return Err(ApiError::UpstreamError {
                code: format!("OSDR_{}", resp.status().as_u16()),
                message: format!("OSDR request failed with status {}", resp.status()),
            });
        }
        
        let json: Value = resp.json().await?;
        Ok(json)
    }
}

#[derive(Clone)]
pub struct IssClient {
    client: Client,
}

impl IssClient {
    pub fn new() -> Self {
        let client = Client::builder()
            .timeout(Duration::from_secs(20))
            .user_agent("rust-iss/1.0")
            .build()
            .unwrap();

        Self { client }
    }

    pub async fn fetch_position(&self, url: &str) -> Result<Value, ApiError> {
        debug!("Fetching ISS position from: {}", url);
        let json: Value = self.client.get(url).send().await?.json().await?;
        Ok(json)
    }
}

#[derive(Clone)]
pub struct SpaceXClient {
    client: Client,
}

impl SpaceXClient {
    pub fn new() -> Self {
        let client = Client::builder()
            .timeout(Duration::from_secs(30))
            .user_agent("rust-iss/1.0")
            .build()
            .unwrap();

        Self { client }
    }

    pub async fn fetch_next_launch(&self) -> Result<Value, ApiError> {
        debug!("Fetching next SpaceX launch");
        let url = "https://api.spacexdata.com/v4/launches/next";
        let json: Value = self.client.get(url).send().await?.json().await?;
        Ok(json)
    }
}
