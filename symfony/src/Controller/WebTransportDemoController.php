<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebTransportDemoController extends AbstractController
{
    #[Route('/demo/webtransport', name: 'demo_webtransport', methods: ['GET'])]
    public function __invoke(HttpClientInterface $http): Response
    {
        $wtUrl = $_ENV['WT_URL'] ?? 'https://localhost:8444/';
        $gatewayBase = $_ENV['GATEWAY_HTTP_BASE_URL'] ?? 'http://gateway:8080';

        $info = $http->request('GET', rtrim($gatewayBase, '/') . '/internal/info')->toArray(false);

        $certBytes = $info['cert_digest_sha256_bytes'] ?? [];
        if (is_string($certBytes)) {
            // Backwards-compatible with older gateway JSON that returned a string like "[1,2,3]".
            $certBytes = trim($certBytes);
            $certBytes = trim($certBytes, "[]");
            $certBytes = $certBytes === '' ? [] : array_map('intval', array_map('trim', explode(',', $certBytes)));
        }

        return $this->render('demo/webtransport.html.twig', [
            'wt_url' => $wtUrl,
            'cert_digest_bytes' => $certBytes,
            'cert_digest_hex' => $info['cert_digest_sha256_dotted_hex'] ?? null,
        ]);
    }
}

