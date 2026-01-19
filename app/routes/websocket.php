<?php

declare(strict_types=1);

use App\Support\Route;

// WebSocket routes
// These define how WebSocket connections are authenticated and routed

Route::ws('/ws', function($connection) {
    // Handle WebSocket connection
    $connection->on('connect', function($data) {
        // Handle connect event
    });
    
    $connection->on('message', function($message) {
        // Handle incoming message
    });
    
    $connection->on('disconnect', function() {
        // Handle disconnect
    });
});


