<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class PingController
{
    public function ping(): JsonResponse
    {
        return new JsonResponse(['pong' => true]);
    }
}

