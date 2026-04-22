#!/usr/bin/env php
<?php

use App\WebSocket\HarvestAlertWebSocket;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$port = 8080;

echo "Starting WebSocket server on port {$port}...\n";
echo "WebSocket URL: ws://localhost:{$port}\n";
echo "Press Ctrl+C to stop\n\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new HarvestAlertWebSocket()
        )
    ),
    $port
);

$server->run();
