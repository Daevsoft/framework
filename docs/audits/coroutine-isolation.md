# AUDIT-05: Coroutine Isolation & Cross-Request State Leakage

This audit runs concurrent Swoole coroutines against shared Context, Container and LifecycleManager instances. Each coroutine repeatedly opens a request scope, stores a unique request identifier, resolves a scoped service, yields to competing coroutines, emits a lifecycle event, and closes the scope. Deterministic lifecycle exceptions exercise cleanup paths.

## Run locally

```sh
composer install
AUDIT_COROUTINES=64 AUDIT_CYCLES=250 php benchmarks/coroutine-isolation-stress.php
```

Swoole with `Swoole\Coroutine::run` is required. The workflow runs weekly, manually, and on relevant source/test changes. Its JSON report is retained for 30 days.

## Pass criteria

- Every configured request cycle completes.
- Context values remain bound to the originating coroutine across yields.
- A request-scoped service is reused within one request and does not leak into another request.
- Lifecycle callbacks observe the matching coroutine-local request identifier.
- All deterministic exceptions are caught by their originating request.
- The shared Context fallback store is empty after all coroutines complete.

## Limitations

This is a synthetic single-process Swoole test, not a multi-worker or production traffic replay. It validates the exercised Context and Container request-scope paths; it does not prove isolation for arbitrary application globals, static properties, user callbacks, third-party extensions, or every cancellation/shutdown interleaving. Review results alongside AUDIT-03 and AUDIT-04.
