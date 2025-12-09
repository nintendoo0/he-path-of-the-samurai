use std::time::Duration;
use tracing::{error, info};

use crate::services::{IssService, OsdrService, SpaceService};

pub fn spawn_background_tasks(
    iss_service: IssService,
    osdr_service: OsdrService,
    space_service: SpaceService,
    iss_interval: u64,
    osdr_interval: u64,
    apod_interval: u64,
    neo_interval: u64,
    donki_interval: u64,
    spacex_interval: u64,
) {
    // ISS position fetcher
    {
        let svc = iss_service.clone();
        tokio::spawn(async move {
            info!("Starting ISS background task (interval: {}s)", iss_interval);
            loop {
                if let Err(e) = svc.fetch_and_store().await {
                    error!("ISS fetch error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(iss_interval)).await;
            }
        });
    }

    // OSDR sync
    {
        let svc = osdr_service.clone();
        tokio::spawn(async move {
            info!("Starting OSDR background task (interval: {}s)", osdr_interval);
            loop {
                if let Err(e) = svc.sync().await {
                    error!("OSDR sync error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(osdr_interval)).await;
            }
        });
    }

    // APOD
    {
        let svc = space_service.clone();
        tokio::spawn(async move {
            info!("Starting APOD background task (interval: {}s)", apod_interval);
            loop {
                if let Err(e) = svc.fetch_apod().await {
                    error!("APOD fetch error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(apod_interval)).await;
            }
        });
    }

    // NEO
    {
        let svc = space_service.clone();
        tokio::spawn(async move {
            info!("Starting NEO background task (interval: {}s)", neo_interval);
            loop {
                if let Err(e) = svc.fetch_neo().await {
                    error!("NEO fetch error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(neo_interval)).await;
            }
        });
    }

    // DONKI (FLR + CME)
    {
        let svc = space_service.clone();
        tokio::spawn(async move {
            info!("Starting DONKI background task (interval: {}s)", donki_interval);
            loop {
                let _ = svc.fetch_donki_flr().await;
                let _ = svc.fetch_donki_cme().await;
                tokio::time::sleep(Duration::from_secs(donki_interval)).await;
            }
        });
    }

    // SpaceX
    {
        let svc = space_service;
        tokio::spawn(async move {
            info!("Starting SpaceX background task (interval: {}s)", spacex_interval);
            loop {
                if let Err(e) = svc.fetch_spacex().await {
                    error!("SpaceX fetch error: {:?}", e);
                }
                tokio::time::sleep(Duration::from_secs(spacex_interval)).await;
            }
        });
    }
}
