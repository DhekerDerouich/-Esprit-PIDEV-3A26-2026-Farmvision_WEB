<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class HarvestAlertWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections; // Map user IDs to connections

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data) {
            return;
        }

        // Handle user registration
        if (isset($data['type']) && $data['type'] === 'register') {
            $userId = $data['userId'];
            $this->userConnections[$userId] = $from;
            $from->send(json_encode([
                'type' => 'registered',
                'message' => 'Successfully registered for harvest alerts',
                'userId' => $userId
            ]));
            echo "User {$userId} registered for alerts\n";
            return;
        }

        // Handle broadcast request (from server/command)
        if (isset($data['type']) && $data['type'] === 'broadcast_alert') {
            $userId = $data['userId'];
            $alertData = $data['alert'];
            
            if (isset($this->userConnections[$userId])) {
                $this->userConnections[$userId]->send(json_encode([
                    'type' => 'harvest_alert',
                    'alert' => $alertData
                ]));
                echo "Alert sent to user {$userId}\n";
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove connection
        $this->clients->detach($conn);
        
        // Remove from user connections
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                echo "User {$userId} disconnected\n";
                break;
            }
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    /**
     * Broadcast alert to specific user
     */
    public function broadcastToUser(int $userId, array $alertData): bool
    {
        if (isset($this->userConnections[$userId])) {
            $this->userConnections[$userId]->send(json_encode([
                'type' => 'harvest_alert',
                'alert' => $alertData
            ]));
            return true;
        }
        return false;
    }

    /**
     * Broadcast to all connected clients
     */
    public function broadcastToAll(array $data): void
    {
        foreach ($this->clients as $client) {
            $client->send(json_encode($data));
        }
    }
}
