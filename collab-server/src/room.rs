use std::sync::atomic::{AtomicBool, AtomicI64, AtomicUsize, Ordering};
use std::sync::Arc;
use std::time::{Duration, Instant};

use dashmap::DashMap;
use parking_lot::RwLock;
use yrs::{Doc, GetString, Text, Transact};
use yrs::sync::Awareness;
use yrs_axum::AwarenessRef;

pub struct Room {
    pub note_id: i64,
    pub awareness: AwarenessRef,
    pub dirty: AtomicBool,
    pub last_activity: RwLock<Instant>,
    pub peer_count: AtomicUsize,
    pub last_writer: AtomicI64,
}

impl Room {
    pub fn new(note_id: i64, initial_body: &str) -> Self {
        let doc = Doc::new();
        {
            let text = doc.get_or_insert_text("body");
            let mut txn = doc.transact_mut();
            text.push(&mut txn, initial_body);
        }

        Self {
            note_id,
            awareness: Arc::new(tokio::sync::RwLock::new(Awareness::new(doc))),
            dirty: AtomicBool::new(false),
            last_activity: RwLock::new(Instant::now()),
            peer_count: AtomicUsize::new(0),
            last_writer: AtomicI64::new(0),
        }
    }

    pub async fn get_body(&self) -> String {
        let awareness = self.awareness.read().await;
        let doc = awareness.doc();
        let text = doc.get_or_insert_text("body");
        let txn = doc.transact();
        text.get_string(&txn)
    }

    pub fn mark_dirty(&self, writer_id: i64) {
        self.dirty.store(true, Ordering::Release);
        self.last_writer.store(writer_id, Ordering::Release);
        *self.last_activity.write() = Instant::now();
    }

    pub fn add_peer(&self) -> usize {
        *self.last_activity.write() = Instant::now();
        self.peer_count.fetch_add(1, Ordering::SeqCst) + 1
    }

    pub fn remove_peer(&self) -> usize {
        *self.last_activity.write() = Instant::now();
        self.peer_count.fetch_sub(1, Ordering::SeqCst) - 1
    }
}

pub struct RoomRegistry {
    rooms: DashMap<i64, Arc<Room>>,
}

impl RoomRegistry {
    pub fn new() -> Self {
        Self {
            rooms: DashMap::new(),
        }
    }

    pub fn get(&self, note_id: i64) -> Option<Arc<Room>> {
        self.rooms.get(&note_id).map(|r| r.value().clone())
    }

    pub fn get_or_insert_with(&self, note_id: i64, body: &str) -> Arc<Room> {
        self.rooms
            .entry(note_id)
            .or_insert_with(|| Arc::new(Room::new(note_id, body)))
            .value()
            .clone()
    }

    pub fn note_ids(&self) -> Vec<i64> {
        self.rooms.iter().map(|r| *r.key()).collect()
    }

    pub fn remove(&self, note_id: i64) -> Option<Arc<Room>> {
        self.rooms.remove(&note_id).map(|(_, r)| r)
    }

    pub fn dirty_rooms(&self) -> Vec<Arc<Room>> {
        self.rooms
            .iter()
            .filter(|r| r.value().dirty.load(Ordering::Acquire))
            .map(|r| r.value().clone())
            .collect()
    }

    pub fn idle_rooms(&self, ttl: Duration) -> Vec<i64> {
        self.rooms
            .iter()
            .filter(|r| {
                let room = r.value();
                room.peer_count.load(Ordering::SeqCst) == 0
                    && room.last_activity.read().elapsed() > ttl
            })
            .map(|r| *r.key())
            .collect()
    }
}
