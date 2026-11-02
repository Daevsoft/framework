<?php

declare(strict_types=1);

namespace Tests\Routing;

use Ds\Http\Request;
use PHPUnit\Framework\TestCase;

final class SwooleRequestTest extends TestCase
{
    public function testBuildsRequestFromSwoolePayloadWithoutPhpGlobals(): void
    {
        $swooleRequest = new FakeSwooleHttpRequest();
        $request = Request::fromSwoole($swooleRequest);

        self::assertSame('POST', $request->getMethod());
        self::assertSame('/api/users/9', $request->getPath());
        self::assertSame('9', $request->getQuery('page'));
        self::assertSame('application/json', $request->getHeader('Content-Type'));
        self::assertSame('Ada', $request->input('name'));
        self::assertSame('9', $request->input('id'));
    }
}

final class FakeSwooleHttpRequest
{
    public array $server = [
        'request_uri' => '/api/users/9?page=9',
        'request_method' => 'POST',
    ];
    public array $header = ['Content-Type' => 'application/json'];
    public array $get = ['page' => '9'];
    public array $post = [];

    public function rawContent(): string
    {
        return '{"name":"Ada","id":"9"}';
    }
}
