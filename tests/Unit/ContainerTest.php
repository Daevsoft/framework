<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Container\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContainerTest extends TestCase
{
    public function testAutomaticallyResolvesConstructorDependencies(): void
    {
        $container = new Container();
        $service = $container->make(ContainerTestService::class);

        self::assertInstanceOf(ContainerTestService::class, $service);
        self::assertInstanceOf(ContainerTestDependency::class, $service->dependency);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(ContainerTestDependency::class);

        self::assertSame(
            $container->make(ContainerTestDependency::class),
            $container->make(ContainerTestDependency::class)
        );
    }

    public function testDetectsCircularDependencies(): void
    {
        $container = new Container();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $container->make(ContainerTestCircularA::class);
    }
}

final class ContainerTestDependency
{
}

final class ContainerTestService
{
    public function __construct(public ContainerTestDependency $dependency)
    {
    }
}

final class ContainerTestCircularA
{
    public function __construct(ContainerTestCircularB $dependency)
    {
    }
}

final class ContainerTestCircularB
{
    public function __construct(ContainerTestCircularA $dependency)
    {
    }
}
