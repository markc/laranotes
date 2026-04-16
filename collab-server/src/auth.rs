use std::collections::HashMap;

use base64::{engine::general_purpose::URL_SAFE_NO_PAD, Engine as _};
use hmac::{Hmac, Mac};
use serde::Deserialize;
use sha2::Sha256;
use subtle::ConstantTimeEq;

use crate::error::AppError;

type HmacSha256 = Hmac<Sha256>;

#[derive(Debug, Deserialize)]
pub struct Claims {
    pub sub: i64,
    pub note: i64,
    pub can_edit: bool,
    pub exp: i64,
    pub iat: i64,
    pub kid: String,
}

pub fn verify_token(
    token: &str,
    secrets: &HashMap<String, String>,
) -> Result<Claims, AppError> {
    let (payload_b64, sig_b64) = token
        .split_once('.')
        .ok_or(AppError::InvalidToken)?;

    let payload_bytes = URL_SAFE_NO_PAD
        .decode(payload_b64)
        .map_err(|_| AppError::InvalidToken)?;

    let claims: Claims =
        serde_json::from_slice(&payload_bytes).map_err(|_| AppError::InvalidToken)?;

    let now = std::time::SystemTime::now()
        .duration_since(std::time::UNIX_EPOCH)
        .unwrap()
        .as_secs() as i64;

    if claims.exp < now - 30 {
        return Err(AppError::InvalidToken);
    }

    let secret = secrets
        .get(&claims.kid)
        .ok_or(AppError::InvalidToken)?;

    let mut mac = HmacSha256::new_from_slice(secret.as_bytes())
        .map_err(|_| AppError::InvalidToken)?;
    mac.update(payload_b64.as_bytes());
    let expected = mac.finalize().into_bytes();

    let sig_bytes = URL_SAFE_NO_PAD
        .decode(sig_b64)
        .map_err(|_| AppError::InvalidToken)?;

    if expected.len() != sig_bytes.len()
        || expected.as_slice().ct_eq(&sig_bytes).unwrap_u8() != 1
    {
        return Err(AppError::InvalidToken);
    }

    Ok(claims)
}
