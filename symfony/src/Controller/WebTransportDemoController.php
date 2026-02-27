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

        return $this->render('demo/webtransport.html.twig', [
            'wt_url' => $wtUrl,
            'cert_digest_bytes' => $certBytes,
            'cert_digest_hex' => $info['cert_digest_sha256_dotted_hex'] ?? null,
        ]);
    }
}
