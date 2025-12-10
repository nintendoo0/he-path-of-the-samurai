use chrono::{DateTime, Utc};
use serde_json::Value;
use sqlx::{PgPool, Row};

use crate::domain::{IssPosition, IssTrend, IssHistoryPoint, OsdrItem, SpaceData};
use crate::error::ApiError;
use crate::utils::{haversine_km, num};

#[derive(Clone)]
pub struct IssRepository {
    pool: PgPool,
}

impl IssRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn insert(&self, source_url: &str, payload: Value) -> Result<(), ApiError> {
        sqlx::query("INSERT INTO iss_fetch_log (source_url, payload) VALUES ($1, $2)")
            .bind(source_url)
            .bind(payload)
            .execute(&self.pool)
            .await?;
        Ok(())
    }

    pub async fn get_last(&self) -> Result<Option<IssPosition>, ApiError> {
        let row_opt = sqlx::query(
            "SELECT id, fetched_at, source_url, payload
             FROM iss_fetch_log
             ORDER BY id DESC LIMIT 1",
        )
        .fetch_optional(&self.pool)
        .await?;

        if let Some(row) = row_opt {
            Ok(Some(IssPosition {
                id: row.get("id"),
                fetched_at: row.get("fetched_at"),
                source_url: row.get("source_url"),
                payload: row.try_get("payload").unwrap_or(serde_json::json!({})),
            }))
        } else {
            Ok(None)
        }
    }

    pub async fn get_trend(&self) -> Result<IssTrend, ApiError> {
        let rows = sqlx::query(
            "SELECT fetched_at, payload FROM iss_fetch_log ORDER BY id DESC LIMIT 2",
        )
        .fetch_all(&self.pool)
        .await?;

        if rows.len() < 2 {
            return Ok(IssTrend {
                movement: false,
                delta_km: 0.0,
                dt_sec: 0.0,
                velocity_kmh: None,
                from_time: None,
                to_time: None,
                from_lat: None,
                from_lon: None,
                to_lat: None,
                to_lon: None,
            });
        }

        let t2: DateTime<Utc> = rows[0].get("fetched_at");
        let t1: DateTime<Utc> = rows[1].get("fetched_at");
        let p2: Value = rows[0].get("payload");
        let p1: Value = rows[1].get("payload");

        let lat1 = num(&p1["latitude"]);
        let lon1 = num(&p1["longitude"]);
        let lat2 = num(&p2["latitude"]);
        let lon2 = num(&p2["longitude"]);
        let v2 = num(&p2["velocity"]);

        let mut delta_km = 0.0;
        let mut movement = false;
        if let (Some(a1), Some(o1), Some(a2), Some(o2)) = (lat1, lon1, lat2, lon2) {
            delta_km = haversine_km(a1, o1, a2, o2);
            movement = delta_km > 0.1;
        }
        let dt_sec = (t2 - t1).num_milliseconds() as f64 / 1000.0;

        Ok(IssTrend {
            movement,
            delta_km,
            dt_sec,
            velocity_kmh: v2,
            from_time: Some(t1),
            to_time: Some(t2),
            from_lat: lat1,
            from_lon: lon1,
            to_lat: lat2,
            to_lon: lon2,
        })
    }

    pub async fn get_history(&self, limit: i64) -> Result<Vec<IssHistoryPoint>, ApiError> {
        let rows = sqlx::query(
            "SELECT fetched_at, payload FROM iss_fetch_log ORDER BY id DESC LIMIT $1",
        )
        .bind(limit)
        .fetch_all(&self.pool)
        .await?;

        let mut points = Vec::new();
        for row in rows {
            let at: DateTime<Utc> = row.get("fetched_at");
            let payload: Value = row.get("payload");
            
            let lat = num(&payload["latitude"]).unwrap_or(0.0);
            let lon = num(&payload["longitude"]).unwrap_or(0.0);
            let altitude = num(&payload["altitude"]).unwrap_or(0.0);
            let velocity = num(&payload["velocity"]).unwrap_or(0.0);
            
            points.push(IssHistoryPoint {
                at,
                lat,
                lon,
                altitude,
                velocity,
            });
        }
        
        // Разворачиваем, чтобы старые точки были первыми
        points.reverse();
        Ok(points)
    }
}

#[derive(Clone)]
pub struct OsdrRepository {
    pool: PgPool,
}

impl OsdrRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn upsert_item(
        &self,
        dataset_id: Option<String>,
        title: Option<String>,
        status: Option<String>,
        updated_at: Option<DateTime<Utc>>,
        raw: Value,
    ) -> Result<(), ApiError> {
        if let Some(ds) = dataset_id.clone() {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)
                 ON CONFLICT (dataset_id) DO UPDATE
                 SET title=EXCLUDED.title, status=EXCLUDED.status,
                     updated_at=EXCLUDED.updated_at, raw=EXCLUDED.raw",
            )
            .bind(ds)
            .bind(title)
            .bind(status)
            .bind(updated_at)
            .bind(raw)
            .execute(&self.pool)
            .await?;
        } else {
            sqlx::query(
                "INSERT INTO osdr_items(dataset_id, title, status, updated_at, raw)
                 VALUES($1,$2,$3,$4,$5)",
            )
            .bind::<Option<String>>(None)
            .bind(title)
            .bind(status)
            .bind(updated_at)
            .bind(raw)
            .execute(&self.pool)
            .await?;
        }
        Ok(())
    }

    pub async fn list(&self, limit: i64) -> Result<Vec<OsdrItem>, ApiError> {
        let rows = sqlx::query(
            "SELECT id, dataset_id, title, status, updated_at, inserted_at, raw
             FROM osdr_items
             ORDER BY inserted_at DESC
             LIMIT $1",
        )
        .bind(limit)
        .fetch_all(&self.pool)
        .await?;

        let items = rows
            .into_iter()
            .map(|r| OsdrItem {
                id: r.get("id"),
                dataset_id: r.get("dataset_id"),
                title: r.get("title"),
                status: r.get("status"),
                updated_at: r.get("updated_at"),
                inserted_at: r.get("inserted_at"),
                raw: r.get("raw"),
            })
            .collect();

        Ok(items)
    }

    pub async fn count(&self) -> Result<i64, ApiError> {
        let row = sqlx::query("SELECT count(*) AS c FROM osdr_items")
            .fetch_one(&self.pool)
            .await?;
        Ok(row.get("c"))
    }
}

#[derive(Clone)]
pub struct CacheRepository {
    pool: PgPool,
}

impl CacheRepository {
    pub fn new(pool: PgPool) -> Self {
        Self { pool }
    }

    pub async fn insert(&self, source: &str, payload: Value) -> Result<(), ApiError> {
        sqlx::query("INSERT INTO space_cache(source, payload) VALUES ($1,$2)")
            .bind(source)
            .bind(payload)
            .execute(&self.pool)
            .await?;
        Ok(())
    }

    pub async fn get_latest(&self, source: &str) -> Result<Option<SpaceData>, ApiError> {
        let row_opt = sqlx::query(
            "SELECT fetched_at, payload FROM space_cache
             WHERE source = $1 ORDER BY id DESC LIMIT 1",
        )
        .bind(source)
        .fetch_optional(&self.pool)
        .await?;

        if let Some(r) = row_opt {
            Ok(Some(SpaceData {
                source: source.to_string(),
                fetched_at: r.get("fetched_at"),
                payload: r.get("payload"),
            }))
        } else {
            Ok(None)
        }
    }
}
