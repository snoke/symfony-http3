<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class InternalEventsController
{
    public function events(Request $request): JsonResponse
    {
        // Placeholder for gateway -> Symfony events.
        return new JsonResponse([
            'ok' => true,
            'received' => $request->getContent() !== '' ? 'body' : 'empty',
        ]);
    }
}

