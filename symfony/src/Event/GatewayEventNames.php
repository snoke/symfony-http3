<?php

namespace App\Event;

final class GatewayEventNames
{
    public const ON_CONNECTED = 'gateway.onConnected';
    public const ON_MESSAGE_RECEIVED = 'gateway.onMessageReceived';
    public const ON_MESSAGE_SENT = 'gateway.onMessageSent';
    public const ON_DISCONNECTED = 'gateway.onDisconnected';
    public const ON_UNKNOWN = 'gateway.onUnknown';
}

