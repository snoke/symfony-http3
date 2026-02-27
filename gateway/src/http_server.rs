use anyhow::Context;
use anyhow::Result;
use axum::routing::get;
use axum::Json;
use axum::Router;
use serde_json::json;
use std::net::Ipv4Addr;
use std::net::SocketAddr;
use tokio::net::TcpListener;
use wtransport::tls::Sha256Digest;
use wtransport::tls::Sha256DigestFmt;

pub struct HttpServer {
    serve: axum::serve::Serve<Router, Router>,
    local_port: u16,
}

impl HttpServer {
    pub async fn new(http_port: u16, cert_digest: &Sha256Digest) -> Result<Self> {
        let router = build_router(cert_digest);

        let listener = TcpListener::bind(SocketAddr::new(Ipv4Addr::UNSPECIFIED.into(), http_port))
            .await
            .context("cannot bind HTTP listener")?;

        let local_port = listener.local_addr().context("cannot get local addr")?.port();

        Ok(Self {
            serve: axum::serve(listener, router),
            local_port,
        })
    }

    pub fn local_port(&self) -> u16 {
        self.local_port
    }

    pub async fn serve(self) -> Result<()> {
        self.serve.await.context("HTTP server error")?;
        Ok(())
    }
}

fn build_router(cert_digest: &Sha256Digest) -> Router {
    let cert_bytes: Vec<u8> = cert_digest.as_ref().to_vec();
    let cert_dotted_hex = cert_digest.fmt(Sha256DigestFmt::DottedHex);

    let info_handler = {
        let cert_bytes = cert_bytes.clone();
        let cert_dotted_hex = cert_dotted_hex.clone();
        move || {
            let cert_bytes = cert_bytes.clone();
            let cert_dotted_hex = cert_dotted_hex.clone();
            async move { info(cert_bytes, cert_dotted_hex) }
        }
    };

    Router::new()
        .route("/health", get(health))
        .route("/api/ping", get(ping))
        .route("/internal/info", get(info_handler))
}

async fn health() -> &'static str {
    "ok"
}

async fn ping() -> Json<serde_json::Value> {
    Json(json!({"pong": true}))
}

fn info(cert_digest_bytes: Vec<u8>, cert_digest_dotted_hex: String) -> Json<serde_json::Value> {
    Json(json!({
        "cert_digest_sha256_bytes": cert_digest_bytes,
        "cert_digest_sha256_dotted_hex": cert_digest_dotted_hex
    }))
}
