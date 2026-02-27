<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class InternalEventsController
{
    #[Route('/internal/ws/events', name: 'internal_events', methods: ['POST'])]
    public function events(Request $request): JsonResponse
    {
        // Placeholder for gateway -> Symfony events.
        return new JsonResponse([
            'ok' => true,
            'received' => $request->getContent() !== '' ? 'body' : 'empty',
        ]);
    }
}
