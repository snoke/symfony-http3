<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Webhook event envelope sent from the Rust gateway to Symfony.
 *
 * Note: This is NOT an HTTP/3 request. HTTP/3/WebTransport is Browser -> Gateway.
 * Symfony receives a regular HTTP webhook POST from the gateway.
 */
final class GatewayWebhookEvent extends Event
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $type,
        public readonly string $connectionId,
        public readonly string $transport,
        public readonly ?string $payload,
        public readonly array $raw,
    ) {
    }
}

