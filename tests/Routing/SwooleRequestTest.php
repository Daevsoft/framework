<?php

declare(strict_types=1);

namespace Tests\Routing;

use Ds\Container\Container;
use Ds\Context\Context;
use Ds\Http\Request;
use Ds\Http\Response;
use Ds\Lifecycle\LifecycleManager;
use Ds\Server\SwooleServer;
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

    public function testSequentialNonCoroutineRequestsCannotReadPreviousRequestContext(): void
    {
        $context = Context::default();
        $context->clear();
        $container = new Container();
        $lifecycle = new LifecycleManager();
        $observed = 'not-run';

        $kernelFactory = static function () use (&$observed): object {
            return new class(static function (mixed $value) use (&$observed): void {
                $observed = $value;
            }) {
                public function __construct(private \Closure $observe)
                {
                }

                public function handle(Request $request): Response
                {
                    $context = Context::default();
                    ($this->observe)($context->get('request.secret'));
                    if ($request->getPath() === '/first') {
                        $context->set('request.secret', 'private-first-request');
                    }

                    return Response::text('ok');
                }
            };
        };

        $method = new \ReflectionMethod(SwooleServer::class, 'handleRequest');
        $method->setAccessible(true);

        $firstResponse = new FakeSwooleHttpResponse();
        $method->invoke(null, $container, $kernelFactory, $lifecycle, new FakeSwooleHttpRequest('/first'), $firstResponse);
        self::assertSame('ok', $firstResponse->body);
        self::assertNull($context->get('request.secret'));

        $secondResponse = new FakeSwooleHttpResponse();
        $method->invoke(null, $container, $kernelFactory, $lifecycle, new FakeSwooleHttpRequest('/second'), $secondResponse);

        self::assertNull($observed, 'The second request must not observe data written by the first request.');
        self::assertNull($context->get('request.secret'));
    }

    public function testRequestHandledListenerFailureStillClearsFallbackContext(): void
    {
        $context = Context::default();
        $context->clear();
        $container = new Container();
        $lifecycle = new LifecycleManager();
        $lifecycle->register('request.handled', static function (): void {
            throw new \RuntimeException('listener failure');
        });

        $kernel = static function (): object {
            return new class {
                public function handle(Request $request): Response
                {
                    Context::default()->set('request.secret', 'must-be-cleared');
                    return Response::text('ok');
                }
            };
        };

        $method = new \ReflectionMethod(SwooleServer::class, 'handleRequest');
        $method->setAccessible(true);
        $method->invoke(null, $container, $kernel, $lifecycle, new FakeSwooleHttpRequest('/failure'), new FakeSwooleHttpResponse());

        self::assertNull($context->get('request.secret'));
    }
}

final class FakeSwooleHttpRequest
{
    public array $server;
    public array $header = ['Content-Type' => 'application/json'];
    public array $get = ['page' => '9'];
    public array $post = [];

    public function __construct(string $path = '/api/users/9?page=9')
    {
        $this->server = [
            'request_uri' => $path,
            'request_method' => 'POST',
        ];
    }

    public function rawContent(): string
    {
        return '{"name":"Ada","id":"9"}';
    }
}

final class FakeSwooleHttpResponse
{
    public int $httpStatus = 200;
    public string $body = '';
    public array $headers = [];

    public function header(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function status(int $status): void
    {
        $this->httpStatus = $status;
    }

    public function end(string $body): void
    {
        $this->body = $body;
    }
}
