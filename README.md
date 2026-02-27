# Symfony + Rust WebTransport Gateway (Scaffold)

This repo is a scaffold for a Symfony backend + Rust gateway setup where the Rust gateway terminates
TLS + HTTP/3 (QUIC) and speaks WebTransport directly (no Envoy/Caddy required for dev).

## Run (dev)
1. Start:
   ```bash
   docker compose up --build
   ```
2. Verify:
   - Gateway dev UI (WebTransport client): `http://localhost:8182/`
   - Gateway health: `http://localhost:8182/health`
   - Symfony ping: `http://localhost:8183/api/ping`
3. WebTransport:
   - Server endpoint: `https://localhost:8444/`
   - The dev UI pins the gateway's self-signed cert via `serverCertificateHashes`, so you don't need
     to trust a local CA.
