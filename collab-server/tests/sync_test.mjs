/**
 * Phase 2 sync test — two y-websocket clients syncing through the Rust collab server.
 *
 * Usage:
 *   1. Start the collab server: cd collab-server && cargo run
 *   2. Run this test:           bun run tests/sync_test.mjs
 *
 * Requires: yjs, y-websocket, ws (WebSocket polyfill for Node/Bun)
 */

import * as Y from 'yjs';
import { WebsocketProvider } from 'y-websocket';
import { createHmac } from 'crypto';
import WebSocket from 'ws';

const SERVER_URL = 'ws://localhost:4444/ws/note';
const SECRET = 'laranotes-dev-collab-secret-change-in-production';
const KID = 'v1';
const NOTE_ID = 999;

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

function mintExpiredToken(sub, note) {
  const payload = JSON.stringify({
    sub, note, can_edit: true,
    exp: Math.floor(Date.now() / 1000) - 600,
    iat: Math.floor(Date.now() / 1000) - 900,
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

// --- Test 1: Two clients sync ---
async function testSync() {
  console.log('\nTest 1: Two clients sync a Y.Text');

  const tokenA = mintToken(1, NOTE_ID);
  const tokenB = mintToken(2, NOTE_ID);

  const docA = new Y.Doc();
  const docB = new Y.Doc();

  const providerA = new WebsocketProvider(SERVER_URL, String(NOTE_ID), docA, {
    params: { token: tokenA },
    WebSocketPolyfill: WebSocket,
  });

  // Wait for A to connect
  await new Promise(resolve => {
    providerA.on('status', ({ status }) => {
      if (status === 'connected') resolve();
    });
  });

  const providerB = new WebsocketProvider(SERVER_URL, String(NOTE_ID), docB, {
    params: { token: tokenB },
    WebSocketPolyfill: WebSocket,
  });

  // Wait for B to connect
  await new Promise(resolve => {
    providerB.on('status', ({ status }) => {
      if (status === 'connected') resolve();
    });
  });

  // Client A writes
  const textA = docA.getText('body');
  textA.insert(0, 'hello');

  // Wait for sync
  await sleep(500);

  // Client B reads
  const textB = docB.getText('body');
  const result = textB.toString();

  assert(result === 'hello', `Client B received "${result}" (expected "hello")`);

  providerA.destroy();
  providerB.destroy();
  docA.destroy();
  docB.destroy();

  await sleep(200);
}

// --- Test 2: Invalid token rejected ---
async function testInvalidToken() {
  console.log('\nTest 2: Invalid token rejected');

  const httpUrl = SERVER_URL.replace('ws://', 'http://');
  const res = await fetch(`${httpUrl}/${NOTE_ID}?token=garbage.token`, {
    headers: { 'Connection': 'Upgrade', 'Upgrade': 'websocket', 'Sec-WebSocket-Version': '13', 'Sec-WebSocket-Key': 'dGVzdA==' },
  });
  assert(res.status === 401, `Got status ${res.status} (expected 401)`);
}

// --- Test 3: Token note mismatch rejected ---
async function testNoteMismatch() {
  console.log('\nTest 3: Token note mismatch rejected');

  const token = mintToken(1, 888); // token for note 888
  const httpUrl = SERVER_URL.replace('ws://', 'http://');
  const res = await fetch(`${httpUrl}/${NOTE_ID}?token=${encodeURIComponent(token)}`, {
    headers: { 'Connection': 'Upgrade', 'Upgrade': 'websocket', 'Sec-WebSocket-Version': '13', 'Sec-WebSocket-Key': 'dGVzdA==' },
  });
  assert(res.status === 403, `Got status ${res.status} (expected 403)`);
}

// --- Test 4: Expired token rejected ---
async function testExpiredToken() {
  console.log('\nTest 4: Expired token rejected');

  const token = mintExpiredToken(1, NOTE_ID);
  const httpUrl = SERVER_URL.replace('ws://', 'http://');
  const res = await fetch(`${httpUrl}/${NOTE_ID}?token=${encodeURIComponent(token)}`, {
    headers: { 'Connection': 'Upgrade', 'Upgrade': 'websocket', 'Sec-WebSocket-Version': '13', 'Sec-WebSocket-Key': 'dGVzdA==' },
  });
  assert(res.status === 401, `Got status ${res.status} (expected 401)`);
}

// --- Run all tests ---
async function main() {
  console.log('Phase 2 — Yjs sync protocol tests');
  console.log('='.repeat(40));

  await testInvalidToken();
  await testNoteMismatch();
  await testExpiredToken();
  await testSync();

  console.log(`\n${'='.repeat(40)}`);
  console.log(`${passed} passed, ${failed} failed`);
  process.exit(failed > 0 ? 1 : 0);
}

main().catch(e => {
  console.error('Fatal:', e);
  process.exit(1);
});
