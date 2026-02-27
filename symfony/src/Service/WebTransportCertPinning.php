<?php

namespace App\Service;

final class WebTransportCertPinning
{
    public function __construct(
        private readonly string $certPemFile,
    ) {
    }

    /**
     * Returns the SHA-256 hash bytes of the DER-encoded server certificate (leaf),
     * suitable for WebTransport `serverCertificateHashes`.
     *
     * @return list<int> 32 bytes as integers 0..255
     */
    public function sha256DerBytes(): array
    {
        return array_values(unpack('C*', $this->sha256DerBinary()));
    }

    /**
     * @return string 32 raw bytes
     */
    public function sha256DerBinary(): string
    {
        $certPem = @file_get_contents($this->certPemFile);
        if ($certPem === false) {
            throw new \RuntimeException(sprintf('Cannot read WT_CERT_PEMFILE=%s', $this->certPemFile));
        }

        $x509 = \openssl_x509_read($certPem);
        if ($x509 === false) {
            throw new \RuntimeException(sprintf('Invalid X509 certificate in %s', $this->certPemFile));
        }

        // WebTransport expects a SHA-256 hash of the DER-encoded certificate.
        $fingerprint = \openssl_x509_fingerprint($x509, 'sha256', true);
        if ($fingerprint === false) {
            throw new \RuntimeException('Failed to compute certificate fingerprint');
        }

        if (strlen($fingerprint) !== 32) {
            throw new \RuntimeException(sprintf('Unexpected SHA-256 fingerprint length: %d', strlen($fingerprint)));
        }

        return $fingerprint;
    }

    public function sha256DerHex(): string
    {
        return bin2hex($this->sha256DerBinary());
    }
}

