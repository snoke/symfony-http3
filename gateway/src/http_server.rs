use anyhow::Context;
use anyhow::Result;
use axum::http::header::CONTENT_TYPE;
use axum::response::Html;
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
    pub async fn new(
        http_port: u16,
        public_webtransport_port: u16,
        cert_digest: &Sha256Digest,
    ) -> Result<Self> {
        let router = build_router(public_webtransport_port, cert_digest);

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

fn build_router(public_webtransport_port: u16, cert_digest: &Sha256Digest) -> Router {
    let cert_digest = cert_digest.fmt(Sha256DigestFmt::BytesArray);
    let wt_url = format!("https://localhost:{}/", public_webtransport_port);

    let wt_url_root = wt_url.clone();
    let wt_url_client = wt_url.clone();

    let root = move || async move {
        Html(http_data::INDEX_DATA.replace("${WT_URL}", &wt_url_root))
    };

    let style = move || async move { ([(CONTENT_TYPE, "text/css")], http_data::STYLE_DATA) };

    let client = move || async move {
        (
            [(CONTENT_TYPE, "application/javascript")],
            http_data::CLIENT_DATA
                .replace("${CERT_DIGEST}", &cert_digest)
                .replace("${WT_URL}", &wt_url_client),
        )
    };

    Router::new()
        .route("/", get(root))
        .route("/style.css", get(style))
        .route("/client.js", get(client))
        .route("/health", get(health))
        .route("/api/ping", get(ping))
}

async fn health() -> &'static str {
    "ok"
}

async fn ping() -> Json<serde_json::Value> {
    Json(json!({"pong": true}))
}

mod http_data {
    pub const INDEX_DATA: &str = r#"
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebTransport Dev UI</title>
    <link rel="stylesheet" href="/style.css">
    <script src="/client.js"></script>
  </head>
  <body>
    <h1>WebTransport Dev UI</h1>

    <p>
      This page connects via WebTransport to:
      <code id="target-url">${WT_URL}</code>
    </p>

    <div class="row">
      <button id="connect" onclick="connect()">Connect</button>
      <button id="send" onclick="sendDatagram()" disabled>Send Datagram</button>
    </div>

    <div class="row">
      <input id="payload" type="text" value="hello from browser" />
    </div>

    <h2>Log</h2>
    <pre id="log"></pre>
  </body>
</html>
"#;

    pub const STYLE_DATA: &str = r#"
body {
  font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
  margin: 2rem;
}
code { background: #f2f2f2; padding: 0.1rem 0.3rem; border-radius: 4px; }
.row { margin: 1rem 0; display: flex; gap: 0.75rem; align-items: center; }
input { min-width: 320px; padding: 0.4rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
button { padding: 0.5rem 0.8rem; }
pre { background: #0b1020; color: #d7e2ff; padding: 1rem; border-radius: 10px; overflow: auto; min-height: 220px; }
"#;

    pub const CLIENT_DATA: &str = r#"
const HASH = new Uint8Array(${CERT_DIGEST});
const WT_URL = "${WT_URL}";

let transport;
let dgramWriter;

function log(line) {
  const el = document.getElementById("log");
  el.textContent += line + "\n";
  el.scrollTop = el.scrollHeight;
}

async function connect() {
  log("Connecting to " + WT_URL);

  try {
    transport = new WebTransport(WT_URL, {
      serverCertificateHashes: [{ algorithm: "sha-256", value: HASH.buffer }]
    });
  } catch (e) {
    log("Failed to create WebTransport: " + e);
    return;
  }

  try {
    await transport.ready;
    log("WebTransport ready");
  } catch (e) {
    log("WebTransport failed: " + e);
    return;
  }

  transport.closed
    .then(() => log("WebTransport closed"))
    .catch((e) => log("WebTransport closed abruptly: " + e));

  dgramWriter = transport.datagrams.writable.getWriter();

  readDatagrams();
  document.getElementById("connect").disabled = true;
  document.getElementById("send").disabled = false;
}

async function sendDatagram() {
  const payload = document.getElementById("payload").value;
  const bytes = new TextEncoder().encode(payload);

  try {
    await dgramWriter.write(bytes);
    log("Sent datagram: " + payload);
  } catch (e) {
    log("Datagram send failed: " + e);
  }
}

async function readDatagrams() {
  const reader = transport.datagrams.readable.getReader();
  const decoder = new TextDecoder("utf-8");
  log("Datagram reader ready");

  while (true) {
    const { value, done } = await reader.read();
    if (done) {
      log("Datagram reader done");
      return;
    }
    log("Received datagram: " + decoder.decode(value));
  }
}

window.connect = connect;
window.sendDatagram = sendDatagram;
"#;
}
