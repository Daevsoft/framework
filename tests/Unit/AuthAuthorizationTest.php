<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Auth\ACL;
use Ds\Auth\PolicyManager;
use Ds\Auth\RBAC;
use PHPUnit\Framework\TestCase;

final class AuthAuthorizationTest extends TestCase
{
    public function testRbacSupportsExactAndWildcardPermissions(): void
    {
        $rbac = new RBAC([
            'admin' => ['*'],
            'editor' => ['posts.*'],
            'viewer' => ['posts.read'],
        ]);

        self::assertTrue($rbac->can('admin', 'users.delete'));
        self::assertTrue($rbac->can('editor', 'posts.update'));
        self::assertTrue($rbac->can('viewer', 'posts.read'));
        self::assertFalse($rbac->can('viewer', 'posts.delete'));
        self::assertFalse($rbac->can('unknown', 'posts.read'));
    }

    public function testAclDefaultsToDenyAndSupportsExplicitGrants(): void
    {
        self::assertFalse((new ACL())->allows('reports.read'));
        self::assertTrue((new ACL(['reports.read']))->allows('reports.read'));
        self::assertTrue((new ACL(['*']))->allows('reports.delete'));
    }

    public function testPolicyManagerRequiresExplicitPolicyAndFailsClosedOnException(): void
    {
        $policies = new PolicyManager();
        self::assertFalse($policies->authorize('post.update'));

        $policies->register('post.update', static fn (array $post): bool => ($post['owner_id'] ?? null) === 7);
        self::assertTrue($policies->authorize('post.update', ['owner_id' => 7]));
        self::assertFalse($policies->authorize('post.update', ['owner_id' => 8]));

        $policies->register('broken', static function (): bool {
            throw new \RuntimeException('policy failure');
        });
        self::assertFalse($policies->authorize('broken'));
    }
}
