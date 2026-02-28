(() => {
  function log(line) {
    const el = document.getElementById("log");
    el.textContent += line + "\n";
    el.scrollTop = el.scrollHeight;
  }

  function getConfig() {
    const el = document.getElementById("wt-config");
    if (!el) {
      throw new Error("Missing #wt-config element");
    }

    const wtUrl = el.dataset.wtUrl;
    if (!wtUrl) {
      throw new Error("Missing data-wt-url on #wt-config");
    }

    let certBytes;
    try {
      certBytes = JSON.parse(el.dataset.certDigestBytes || "[]");
    } catch {
      throw new Error("Invalid data-cert-digest-bytes JSON on #wt-config");
    }

    return { wtUrl, certBytes };
  }

  function init() {
    const { wtUrl, certBytes } = getConfig();
    const options = {};
    if (certBytes.length === 32) {
      const hashBytes = new Uint8Array(certBytes);
      const hashBuffer = hashBytes.buffer.slice(
        hashBytes.byteOffset,
        hashBytes.byteOffset + hashBytes.byteLength
      );
      options.serverCertificateHashes = [
        { algorithm: "sha-256", value: hashBuffer },
      ];
    }

    let transport;
    let dgramWriter;

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

    async function connect() {
      log("Connecting to " + wtUrl);

      try {
        transport = new WebTransport(wtUrl, options);
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

    document.getElementById("connect").addEventListener("click", connect);
    document.getElementById("send").addEventListener("click", sendDatagram);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
