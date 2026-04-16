use anyhow::{Context, Result};
use reqwest::Client;
use serde::Deserialize;

#[derive(Clone)]
pub struct LaravelClient {
    http: Client,
    base_url: String,
    secret: String,
}

#[derive(Deserialize)]
struct BodyResponse {
    body: String,
}

impl LaravelClient {
    pub fn new(base_url: String, secret: String) -> Self {
        Self {
            http: Client::new(),
            base_url,
            secret,
        }
    }

    pub async fn load_body(&self, note_id: i64) -> Result<String> {
        let resp = self
            .http
            .get(format!("{}/api/collab/notes/{}/body", self.base_url, note_id))
            .header("X-Collab-Secret", &self.secret)
            .header("Accept", "application/json")
            .send()
            .await
            .context("collab body request failed")?
            .error_for_status()
            .context("collab body response error")?
            .json::<BodyResponse>()
            .await
            .context("collab body parse failed")?;
        Ok(resp.body)
    }

    pub async fn save_body(&self, note_id: i64, body: &str, updated_by: i64) -> Result<()> {
        self.http
            .put(format!("{}/api/collab/notes/{}/body", self.base_url, note_id))
            .header("X-Collab-Secret", &self.secret)
            .header("Accept", "application/json")
            .json(&serde_json::json!({
                "body": body,
                "updated_by": updated_by,
            }))
            .send()
            .await
            .context("collab save request failed")?
            .error_for_status()
            .context("collab save response error")?;
        Ok(())
    }
}
