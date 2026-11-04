<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Context\Context;
use Ds\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseCompatibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Response::reset();
        Context::default()->remove('request');
    }

    public function testLegacyStaticHeadersAreRequestScopedAndMergedIntoResponse(): void
    {
        Response::header('X-Legacy: first', true, 202);
        Response::header('X-Legacy: second', false);
        Response::header('X-Trace: trace-1');

        $response = Response::consumeLegacyOverrides(Response::text('ok'));

        self::assertSame(202, $response->status());
        self::assertSame('first', $response->headers()['X-Legacy']);
        self::assertSame('trace-1', $response->headers()['X-Trace']);

        $next = Response::consumeLegacyOverrides(Response::text('next'));
        self::assertSame(200, $next->status());
        self::assertArrayNotHasKey('X-Legacy', $next->headers());
    }

    public function testLegacyHeaderRejectsInjectionAndInvalidStatus(): void
    {
        try {
            Response::header("X-Test: safe\r\nInjected: yes");
            self::fail('Expected CRLF header injection to be rejected.');
        } catch (\InvalidArgumentException) {
            self::assertTrue(true);
        }

        $this->expectException(\InvalidArgumentException::class);
        Response::header('X-Test: value', true, 99);
    }

    public function testLegacySwooleAdapterAppliesAndConsumesHeaders(): void
    {
        $target = new FakeLegacySwooleResponse();
        Response::header('X-Compat: active', true, 204);

        Response::applyToSwoole($target);

        self::assertSame(204, $target->statusCode);
        self::assertSame(['X-Compat' => 'active'], $target->headers);
        $next = Response::consumeLegacyOverrides(Response::text('next'));
        self::assertSame(200, $next->status());
    }
}

final class FakeLegacySwooleResponse
{
    public int $statusCode = 200;
    public array $headers = [];

    public function status(int $status): void
    {
        $this->statusCode = $status;
    }

    public function header(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }
}
