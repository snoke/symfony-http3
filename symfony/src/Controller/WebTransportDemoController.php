<?php

namespace App\Controller;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebTransportDemoController
{
    #[Route('/demo/webtransport', name: 'demo_webtransport', methods: ['GET'])]
    public function index(): Response
    {
        $wtUrl = $_ENV['WT_URL'] ?? 'https://localhost:8444/';
        $gatewayBase = $_ENV['GATEWAY_HTTP_BASE_URL'] ?? 'http://gateway:8080';

        // The gateway generates a self-signed cert at runtime; browsers can connect without trusting a CA
        // by pinning the cert hash via `serverCertificateHashes`.
        $client = HttpClient::create();
        $info = $client->request('GET', rtrim($gatewayBase, '/') . '/internal/info')->toArray(false);
        $certBytesArray = $info['cert_digest_sha256_bytes'] ?? '[]';

        $html = <<<HTML
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebTransport Demo</title>
    <style>
      body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 2rem; }
      code { background: #f2f2f2; padding: 0.1rem 0.3rem; border-radius: 4px; }
      .row { margin: 1rem 0; display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
      input { min-width: 320px; padding: 0.4rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
      button { padding: 0.5rem 0.8rem; }
      pre { background: #0b1020; color: #d7e2ff; padding: 1rem; border-radius: 10px; overflow: auto; min-height: 220px; width: min(900px, 100%); }
    </style>
  </head>
  <body>
    <h1>WebTransport Demo</h1>

    <p>
      Target: <code id="target-url">{$wtUrl}</code>
    </p>

    <div class="row">
      <button id="connect">Connect</button>
      <button id="send" disabled>Send Datagram</button>
    </div>

    <div class="row">
      <input id="payload" type="text" value="hello from browser" />
    </div>

    <h2>Log</h2>
    <pre id="log"></pre>

    <script>
      const WT_URL = {$this->jsString($wtUrl)};
      const HASH = new Uint8Array({$certBytesArray});
      let transport;
      let dgramWriter;

      function log(line) {
        const el = document.getElementById("log");
        el.textContent += line + "\\n";
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

      document.getElementById("connect").addEventListener("click", connect);
      document.getElementById("send").addEventListener("click", sendDatagram);
    </script>
  </body>
</html>
HTML;

        return new Response($html);
    }

    private function jsString(string $value): string
    {
        // Minimal escaping so we can safely embed the URL as a JS string literal.
        return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
    }
}
