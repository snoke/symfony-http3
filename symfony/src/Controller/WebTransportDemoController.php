<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebTransportDemoController extends AbstractController
{
    #[Route('/demo/webtransport', name: 'demo_webtransport', methods: ['GET'])]
    public function __invoke(): Response
    {
        $wtUrl = $_ENV['WT_URL'] ?? 'https://localhost:8444/';

        $certPemFile = $_ENV['WT_CERT_PEMFILE'] ?? '/run/certs/dev_cert.pem';
        $certPem = @file_get_contents($certPemFile);
        if ($certPem === false) {
            throw new \RuntimeException(sprintf('Cannot read WT_CERT_PEMFILE=%s', $certPemFile));
        }

        $x509 = \openssl_x509_read($certPem);
        if ($x509 === false) {
            throw new \RuntimeException(sprintf('Invalid X509 certificate in %s', $certPemFile));
        }

        // WebTransport expects a SHA-256 hash of the DER-encoded certificate.
        $fingerprint = \openssl_x509_fingerprint($x509, 'sha256', true);
        if ($fingerprint === false) {
            throw new \RuntimeException('Failed to compute certificate fingerprint');
        }

        $certBytes = array_values(unpack('C*', $fingerprint));

        return $this->render('demo/webtransport.html.twig', [
            'wt_url' => $wtUrl,
            'cert_digest_bytes' => $certBytes,
            'cert_digest_hex' => bin2hex($fingerprint),
        ]);
    }
}
