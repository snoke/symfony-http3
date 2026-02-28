<?php

namespace App\Controller;

use Snoke\Http3Bundle\Service\GatewayCertPinning;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebTransportDemoController extends AbstractController
{
    #[Route('/demo/webtransport', name: 'demo_webtransport', methods: ['GET'])]
    public function __invoke(GatewayCertPinning $gateway): Response
    {
        $wtUrl = $_ENV['WT_URL'] ?? 'https://localhost:8444/';
        $certSpkiBytes = $gateway->sha256DerBytes();
        $certDerBytes = $gateway->sha256CertDerBytes();
        $certHashes = [$certSpkiBytes, $certDerBytes];

        return $this->render('demo/webtransport.html.twig', [
            'wt_url' => $wtUrl,
            'cert_hashes' => $certHashes,
            'cert_digest_hex' => $gateway->sha256DerHex(),
        ]);
    }
}
