<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GatewayEventsController
{
    #[Route('/gateway/events', name: 'gateway_events', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            'ok' => true,
            'received' => $request->getContent() !== '' ? 'body' : 'empty',
        ]);
    }
}

