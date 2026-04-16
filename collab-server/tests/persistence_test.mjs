/**
 * Phase 3 persistence test — verifies the collab server loads from and saves to Laravel.
 *
 * Usage:
 *   1. Start Laravel:        php artisan serve --port=8765
 *   2. Start collab server:  cd collab-server && cargo run
 *   3. Run this test:        bun run collab-server/tests/persistence_test.mjs
 */

import * as Y from 'yjs';
import { WebsocketProvider } from 'y-websocket';
import { createHmac } from 'crypto';
import WebSocket from 'ws';

const SERVER_URL = 'ws://localhost:4444/ws/note';
const LARAVEL_URL = 'http://localhost:8765';
const SECRET = 'laranotes-dev-collab-secret-change-in-production';
const KID = 'v1';

function b64url(buf) {
  return Buffer.from(buf).toString('base64url');
}

function mintToken(sub, note, canEdit = true) {
  const payload = JSON.stringify({
    sub, note, can_edit: canEdit,
    exp: Math.floor(Date.now() / 1000) + 300,
    iat: Math.floor(Date.now() / 1000),
    kid: KID,
  });
  const payloadB64 = b64url(payload);
  const sig = createHmac('sha256', SECRET).update(payloadB64).digest();
  return `${payloadB64}.${b64url(sig)}`;
}

function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

let passed = 0;
let failed = 0;

function assert(condition, msg) {
  if (condition) {
    console.log(`  ✓ ${msg}`);
    passed++;
  } else {
    console.error(`  ✗ ${msg}`);
    failed++;
  }
}

// Find a note ID that exists
async function findNoteId() {
  // Use the S2S endpoint to check note 1 exists
  const res = await fetch(`${LARAVEL_URL}/api/collab/notes/1/body`, {
    headers: {
      'X-Collab-Secret': SECRET,
      'Accept': 'application/json',
    },
  });
  if (res.ok) return 1;

  console.error('  Could not find note 1 — make sure Laravel is running with seeded data');
  process.exit(1);
}

async function testLoadFromLaravel() {
  console.log('\nTest 1: Room loads initial body from Laravel');

  const noteId = await findNoteId();

  // Read the current body from Laravel directly
  const res = await fetch(`${LARAVEL_URL}/api/collab/notes/${noteId}/body`, {
    headers: { 'X-Collab-Secret': SECRET, 'Accept': 'application/json' },
  });
  const { body: originalBody } = await res.json();
  console.log(`  Laravel note ${noteId} body: "${originalBody.substring(0, 50)}..."`);

  // Connect a Yjs client
  const token = mintToken(1, noteId);
  const doc = new Y.Doc();
  const provider = new WebsocketProvider(SERVER_URL, String(noteId), doc, {
    params: { token },
    WebSocketPolyfill: WebSocket,
  });

  // Wait for sync
  await new Promise(resolve => {
    provider.on('sync', (synced) => { if (synced) resolve(); });
  });
  await sleep(500);

  const text = doc.getText('body');
  const received = text.toString();

  assert(received === originalBody, `Client received body matching Laravel (${received.length} chars)`);

  provider.destroy();
  doc.destroy();
  await sleep(200);
}

async function testSaveOnDisconnect() {
  console.log('\nTest 2: Body saves back to Laravel on last disconnect');

  const noteId = await findNoteId();
  const token = mintToken(1, noteId);
  const doc = new Y.Doc();

  const provider = new WebsocketProvider(SERVER_URL, String(noteId), doc, {
    params: { token },
    WebSocketPolyfill: WebSocket,
  });

  await new Promise(resolve => {
    provider.on('sync', (synced) => { if (synced) resolve(); });
  });
  await sleep(300);

  // Append a unique marker
  const marker = `\n<!-- collab-test-${Date.now()} -->`;
  const text = doc.getText('body');
  text.insert(text.length, marker);
  await sleep(300);

  // Disconnect — triggers save
  provider.destroy();
  doc.destroy();
  await sleep(2000); // wait for save round-trip

  // Read back from Laravel
  const res = await fetch(`${LARAVEL_URL}/api/collab/notes/${noteId}/body`, {
    headers: { 'X-Collab-Secret': SECRET, 'Accept': 'application/json' },
  });
  const { body: savedBody } = await res.json();

  assert(savedBody.includes(marker.trim()), `Laravel body contains the marker`);

  // Clean up: remove the marker by saving the body without it
  const cleaned = savedBody.replace(marker, '');
  await fetch(`${LARAVEL_URL}/api/collab/notes/${noteId}/body`, {
    method: 'PUT',
    headers: {
      'X-Collab-Secret': SECRET,
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ body: cleaned, updated_by: 1 }),
  });
}

async function main() {
  console.log('Phase 3 — Persistence tests');
  console.log('='.repeat(40));

  await testLoadFromLaravel();
  await testSaveOnDisconnect();

  console.log(`\n${'='.repeat(40)}`);
  console.log(`${passed} passed, ${failed} failed`);
  process.exit(failed > 0 ? 1 : 0);
}

main().catch(e => {
  console.error('Fatal:', e);
  process.exit(1);
});
