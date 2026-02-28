<?php

namespace App\Controller;

use Snoke\Http3Bundle\Service\GatewayCertPinning;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController extends AbstractController
{
    #[Route('/demo', name: 'demo', methods: ['GET'])]
    public function __invoke(GatewayCertPinning $gateway): Response
    {
        $wtUrl = $_ENV['WT_URL'] ?? 'https://localhost:8444/';
        $disablePinning = filter_var($_ENV['WT_DISABLE_PINNING'] ?? '0', FILTER_VALIDATE_BOOLEAN);

        if ($disablePinning) {
            $certBytes = [];
            $certHex = null;
        } else {
            $certBytes = $gateway->sha256DerBytes();
            $certHex = $gateway->sha256DerHex();
        }

        return $this->render('demo/webtransport.html.twig', [
            'wt_url' => $wtUrl,
            'cert_digest_bytes' => $certBytes,
            'cert_digest_hex' => $certHex,
        ]);
    }
}
