<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Ds\Http\Request;
use Ds\Http\Response;

class HelloController
{
    /**
     * Home page
     */
    public function index(Request $request): Response
    {
        return Response::text('Welcome to the Framework!');
    }

    /**
     * Health check endpoint
     */
    public function health(Request $request): Response
    {
        return Response::json(['status' => 'ok', 'timestamp' => time()]);
    }

    /**
     * List users
     */
    public function users(Request $request): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Smith'],
        ];
        return Response::json($users);
    }

    /**
     * Show single user
     */
    public function showUser(Request $request, int $id): Response
    {
        $user = ['id' => $id, 'name' => 'User ' . $id];
        return Response::json($user);
    }

    /**
     * Create user
     */
    public function createUser(Request $request): Response
    {
        $data = $request->all();
        $user = ['id' => rand(100, 999), ...$data];
        return Response::json($user, 201);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, int $id): Response
    {
        $data = $request->all();
        $user = ['id' => $id, ...$data];
        return Response::json($user);
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, int $id): Response
    {
        return Response::json(['message' => 'User deleted'], 204);
    }
}

