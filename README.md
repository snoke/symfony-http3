# Symfony HTTP3 Stack

This repo is a scaffold for a Symfony backend + Rust gateway setup where the gateway terminates TLS + HTTP/3 (QUIC) and speaks WebTransport directly (no Envoy/Caddy).

Clone (includes the Rust gateway submodule):
```bash
git clone --recurse-submodules git@github.com:snoke/symfony-http3-gateway.git
cd symfony-http3-gateway
```

## Run (dev)
1. Generate dev certs (self-signed, used by the gateway):
   ```bash
   ./gateway/scripts/gen_dev_certs.sh
   ```
2. Start:
   ```bash
   docker compose up --build
   ```
3. Verify:
   - WebTransport demo UI (Twig): `http://localhost:8183/demo/webtransport`
4. WebTransport:
   - Server endpoint: `https://localhost:8444/`
   - The demo UI pins the gateway's self-signed cert via `serverCertificateHashes`, so you don't need
     to trust a local CA.
