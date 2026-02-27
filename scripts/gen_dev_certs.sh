#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CERT_DIR="${ROOT_DIR}/certs"

mkdir -p "${CERT_DIR}"

CERT_PEM="${CERT_DIR}/dev_cert.pem"
KEY_PEM="${CERT_DIR}/dev_key.pem"

if [[ -f "${CERT_PEM}" && -f "${KEY_PEM}" ]]; then
  echo "Dev certs already exist: ${CERT_DIR}"
  exit 0
fi

echo "Generating self-signed dev certs into ${CERT_DIR}"

# CN/SAN must match what the browser connects to (localhost).
openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 3650 \
  -keyout "${KEY_PEM}" \
  -out "${CERT_PEM}" \
  -subj "/CN=localhost" \
  -addext "subjectAltName=DNS:localhost,IP:127.0.0.1,IP:::1"

echo "Wrote:"
echo "  ${CERT_PEM}"
echo "  ${KEY_PEM}"

