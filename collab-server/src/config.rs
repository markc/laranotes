use std::collections::HashMap;
use std::net::SocketAddr;

use anyhow::{Context, Result};

#[derive(Clone)]
pub struct Config {
    pub bind_addr: SocketAddr,
    pub laravel_base_url: String,
    pub collab_secret: String,
    pub token_secrets: HashMap<String, String>,
    pub periodic_save_interval_secs: u64,
    pub idle_room_ttl_secs: u64,
}

impl Config {
    pub fn from_env() -> Result<Self> {
        let bind_addr: SocketAddr = std::env::var("BIND_ADDR")
            .unwrap_or_else(|_| "0.0.0.0:4444".into())
            .parse()
            .context("BIND_ADDR must be a valid socket address")?;

        let laravel_base_url = std::env::var("LARAVEL_BASE_URL")
            .unwrap_or_else(|_| "http://localhost:8765".into());

        let collab_secret =
            std::env::var("COLLAB_SECRET").context("COLLAB_SECRET is required")?;

        let secrets_json = std::env::var("TOKEN_SECRETS_JSON")
            .context("TOKEN_SECRETS_JSON is required")?;
        let token_secrets: HashMap<String, String> =
            serde_json::from_str(&secrets_json).context("TOKEN_SECRETS_JSON must be valid JSON object")?;

        let periodic_save_interval_secs: u64 = std::env::var("PERIODIC_SAVE_INTERVAL_SECS")
            .unwrap_or_else(|_| "30".into())
            .parse()
            .unwrap_or(30);

        let idle_room_ttl_secs: u64 = std::env::var("IDLE_ROOM_TTL_SECS")
            .unwrap_or_else(|_| "300".into())
            .parse()
            .unwrap_or(300);

        Ok(Self {
            bind_addr,
            laravel_base_url,
            collab_secret,
            token_secrets,
            periodic_save_interval_secs,
            idle_room_ttl_secs,
        })
    }
}
