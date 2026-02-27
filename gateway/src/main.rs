use anyhow::Context;
use anyhow::Result;
use tracing::error;
use tracing::info;
use wtransport::Identity;

mod http_server;
mod webtransport_server;

#[tokio::main]
async fn main() -> Result<()> {
    tracing_subscriber::fmt()
        .with_env_filter(tracing_subscriber::EnvFilter::from_default_env())
        .init();

    let http_port = env_u16("HTTP_PORT").unwrap_or(8080);
    let webtransport_port = env_u16("WEBTRANSPORT_PORT").unwrap_or(4433);
    // For local dev we generate a self-signed cert at runtime. The browser client pins it via
    // `serverCertificateHashes` so you don't need to trust a local CA.
    let identity = Identity::self_signed(["localhost", "127.0.0.1", "::1"])
        .context("failed to generate self-signed identity")?;
    let cert_digest = identity
        .certificate_chain()
        .as_slice()
        .get(0)
        .context("missing leaf certificate")?
        .hash();

    let webtransport_server =
        webtransport_server::WebTransportServer::new(identity, webtransport_port)?;
    let http_server = http_server::HttpServer::new(http_port, &cert_digest).await?;

    info!(
        http_port = http_server.local_port(),
        webtransport_port = webtransport_server.local_port(),
        "servers started"
    );
    info!("HTTP health: GET /health, gateway info: GET /internal/info");

    tokio::select! {
        result = http_server.serve() => {
            error!("HTTP server stopped: {:?}", result);
        }
        result = webtransport_server.serve() => {
            error!("WebTransport server stopped: {:?}", result);
        }
    }

    Ok(())
}

fn env_u16(key: &str) -> Option<u16> {
    std::env::var(key).ok()?.parse::<u16>().ok()
}
