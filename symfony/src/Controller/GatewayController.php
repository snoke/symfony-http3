<?php

namespace App\Controller;

use App\Event\GatewayEventNames;
use App\Event\GatewayWebhookEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GatewayController
{
    #[Route('/gateway/events', name: 'gateway_events', methods: ['POST'])]
    public function __invoke(Request $request, EventDispatcherInterface $events): JsonResponse
    {
        $content = $request->getContent();
        if ($content === '') {
            return new JsonResponse([
                'ok' => true,
                'received' => 'empty',
            ]);
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'invalid_json',
            ], 400);
        }

        $type = $data['type'] ?? null;
        $connectionId = $data['connection_id'] ?? null;
        $transport = $data['transport'] ?? null;
        $payload = $data['payload'] ?? null;

        if (!is_string($type) || !is_string($connectionId) || !is_string($transport)) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'invalid_payload',
            ], 400);
        }
        if ($payload !== null && !is_string($payload)) {
            return new JsonResponse([
                'ok' => false,
                'error' => 'invalid_payload',
                'field' => 'payload',
            ], 400);
        }

        $eventName = match ($type) {
            'connected' => GatewayEventNames::ON_CONNECTED,
            'message_received' => GatewayEventNames::ON_MESSAGE_RECEIVED,
            'disconnected' => GatewayEventNames::ON_DISCONNECTED,
            default => GatewayEventNames::ON_UNKNOWN,
        };

        $event = new GatewayWebhookEvent(
            type: $type,
            connectionId: $connectionId,
            transport: $transport,
            payload: $payload,
            raw: $data,
        );

        $events->dispatch($event, $eventName);

        return new JsonResponse([
            'ok' => true,
            'received' => 'body',
            'event' => $eventName,
        ]);
    }
}
