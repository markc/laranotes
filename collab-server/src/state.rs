use std::collections::HashMap;
use std::sync::Arc;

use tokio::sync::Mutex;
use yrs_axum::broadcast::BroadcastGroup;

use crate::config::Config;
use crate::persistence::LaravelClient;
use crate::room::{Room, RoomRegistry};

#[derive(Clone)]
pub struct AppState {
    pub config: Arc<Config>,
    pub rooms: Arc<RoomRegistry>,
    pub laravel: Arc<LaravelClient>,
    broadcasts: Arc<Mutex<HashMap<i64, Arc<BroadcastGroup>>>>,
}

impl AppState {
    pub fn new(config: Config, laravel: LaravelClient) -> Self {
        Self {
            config: Arc::new(config),
            rooms: Arc::new(RoomRegistry::new()),
            laravel: Arc::new(laravel),
            broadcasts: Arc::new(Mutex::new(HashMap::new())),
        }
    }

    pub async fn get_or_create_broadcast(
        &self,
        note_id: i64,
        room: &Arc<Room>,
    ) -> Arc<BroadcastGroup> {
        let mut map = self.broadcasts.lock().await;
        if let Some(bg) = map.get(&note_id) {
            return bg.clone();
        }
        let bg = Arc::new(BroadcastGroup::new(room.awareness.clone(), 128).await);
        map.insert(note_id, bg.clone());
        bg
    }

    pub async fn remove_broadcast(&self, note_id: i64) {
        self.broadcasts.lock().await.remove(&note_id);
    }
}
