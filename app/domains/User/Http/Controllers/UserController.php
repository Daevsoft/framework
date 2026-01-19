<?php

declare(strict_types=1);

namespace App\Domains\User\Http\Controllers;

use Ds\Http\Request;
use Ds\Http\Response;

class UserController
{
    /**
     * List all users
     */
    public function index(Request $request): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];
        return Response::json($users);
    }

    /**
     * Create new user
     */
    public function store(Request $request): Response
    {
        $data = $request->all();
        $user = [
            'id' => rand(100, 999),
            'name' => $data['name'] ?? 'Unknown',
            'email' => $data['email'] ?? '',
        ];
        return Response::json($user);
    }

    /**
     * Show single user
     */
    public function show(Request $request, int $id): Response
    {
        $user = ['id' => $id, 'name' => 'User ' . $id, 'email' => 'user' . $id . '@example.com'];
        return Response::json($user);
    }

    /**
     * Update user
     */
    public function update(Request $request, int $id): Response
    {
        $data = $request->all();
        $user = [
            'id' => $id,
            'name' => $data['name'] ?? 'User',
            'email' => $data['email'] ?? '',
        ];
        return Response::json($user);
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, int $id): Response
    {
        return new Response(204);
    }
}
