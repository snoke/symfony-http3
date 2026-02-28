<?php

namespace App\EventSubscriber;

use Snoke\Http3Bundle\Event\GatewayWebhookEvent;
use Snoke\Http3Bundle\Contract\GatewayPublisherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GatewayEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GatewayPublisherInterface $publisher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GatewayWebhookEvent::ON_CONNECTED => 'onConnected',
            GatewayWebhookEvent::ON_MESSAGE_RECEIVED => 'onMessageReceived',
            GatewayWebhookEvent::ON_MESSAGE_SENT => 'onMessageSent',
            GatewayWebhookEvent::ON_DISCONNECTED => 'onDisconnected',
            GatewayWebhookEvent::ON_ERROR => 'onError',
            GatewayWebhookEvent::ON_UNKNOWN => 'onUnknown',
        ];
    }

    public function onConnected(GatewayWebhookEvent $event): void
    {
        error_log(sprintf('[gateway] onConnected connection_id=%s transport=%s', $event->connectionId, $event->transport));

        // Prove the return-path works: publish a welcome datagram back to the client.
        try {
            $this->publisher->publish($event->connectionId, 'welcome from Symfony');
        } catch (\Throwable $e) {
            error_log(sprintf('[gateway] publish failed: %s', $e->getMessage()));
        }
    }

    public function onMessageReceived(GatewayWebhookEvent $event): void
    {
        $payload = $event->payload ?? '';
        if (strlen($payload) > 200) {
            $payload = substr($payload, 0, 200).'...';
        }

        error_log(sprintf(
            '[gateway] onMessageReceived connection_id=%s transport=%s payload=%s',
            $event->connectionId,
            $event->transport,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ));
    }

    public function onMessageSent(GatewayWebhookEvent $event): void
    {
        // Not used yet (would be Symfony -> Gateway -> Client).
        error_log(sprintf('[gateway] onMessageSent connection_id=%s transport=%s', $event->connectionId, $event->transport));
    }

    public function onDisconnected(GatewayWebhookEvent $event): void
    {
        error_log(sprintf('[gateway] onDisconnected connection_id=%s transport=%s', $event->connectionId, $event->transport));
    }

    public function onError(GatewayWebhookEvent $event): void
    {
        error_log(sprintf('[gateway] onError connection_id=%s transport=%s payload=%s', $event->connectionId, $event->transport, $event->payload ?? ''));
    }

    public function onUnknown(GatewayWebhookEvent $event): void
    {
        error_log(sprintf('[gateway] onUnknown type=%s transport=%s', $event->type, $event->transport));
    }
}
