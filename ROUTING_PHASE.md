# Routing Phase — Compatibility, Completion & Professional DX

Repository: `Daevsoft/framework`  
Target branch: `v4`  
Phase ID: `ROUTING-01`  
Status: **Implementation in progress — focused test execution and performance certification pending**  
Architecture: **Swoole-first; FPM uses the same router/kernel compatibility path**

This document is the implementation contract and task breakdown for completing v4 routing. A checked item means code, regression tests, documentation and relevant verification are complete; it does not mean the item is already implemented.

## 1. Goals and non-goals

### Goals

1. Keep route declarations familiar, concise and discoverable for everyday PHP developers.
2. Support patterns used by professional applications: REST APIs, versioned APIs, admin panels, multi-module applications and SaaS backends.
3. Keep the request hot path predictable: normalize/compile at registration, avoid reflection and regex construction per request, and avoid unnecessary allocations.
4. Preserve Swoole worker safety: route definitions are immutable after bootstrap, request parameters live on the matched request route, and no request/user state is stored in static registries.
5. Keep FPM behavior consistent by using the same matching and dispatch semantics.
6. Preserve existing v3-style route declarations where safe; explicitly document behavior changes instead of silently emulating unsafe behavior.

### Non-goals for this phase

- Do not build a separate routing engine for Swoole and FPM.
- Do not add a dependency on a third-party router unless benchmarks and maintenance benefits justify it.
- Do not add implicit database/model binding, authorization policy, rate limiting or validation into the router. These belong to middleware, controllers, validation and application services.
- Do not add a route cache file by default. Routes are already registered/compiled during bootstrap; a persistent cache is justified only if cold-start benchmarks show a material benefit.
- Do not implement speculative syntax merely because another framework has it. Optional capabilities must have a concrete use case and measurable maintenance/performance cost.

## 2. Current v4 baseline (verified by source inspection)

| Capability | Current state |
|---|---|
| Static routes | Direct lookup by HTTP method and normalized path |
| Dynamic routes | `{name}` placeholders compiled during registration |
| HTTP verbs | GET, POST, PUT, PATCH, DELETE, OPTIONS; WS is exposed by the app DSL |
| Route handlers | Callable, controller class/method and attribute-discovered controller methods |
| Groups | Prefix/callback groups and controller-class groups |
| Middleware | Nested group middleware and per-route middleware |
| Resource routes | Basic seven-action `resource()` registration |
| Route listing | `php ds route:list` command exists |
| Reflection | Attribute discovery occurs during bootstrap, not in matching |
| Missing / not yet complete | Named routes and URL generation, constraints, 405/Allow semantics, HEAD fallback, fallback route, richer resource options and formal edge-case/performance certification |

The current Router keeps static and dynamic routes in separate method registries. Dynamic patterns are compiled on registration. The app-level Route DSL uses static prefix/middleware stacks that are restored in `finally`; this must remain safe under nested registration and must not be used to mutate routes during request handling.

## Implementation progress — 2026-11-05

The following code batches have been pushed to `v4`. Regression tests have been added, but this environment cannot run the repository's PHPUnit suite because the repository checkout/dependencies are not available locally. GitHub Actions have intentionally not been triggered. Therefore, these are **implemented, pending focused test execution and review**, not certified-complete tasks.

| Task | Implementation progress | Remaining verification |
|---|---|---|
| RTE-00 | Inspected v4 router/DSL/dispatcher/attributes/route:list and v3 Route/BaseRoute/RouteData/RouteProvider/attribute sources; detailed findings added to `V3_API_INVENTORY.md` | Exhaustive v4 call-site inventory and application-specific migration review |
| RTE-01 | Duplicate definitions, malformed/repeated placeholders, path normalization and deterministic static-before-dynamic precedence | Full edge-case test run and overlap policy review |
| RTE-02 | Custom and common parameter constraints are validated/warmed at registration; matching captures one segment and validates decoded values; encoded separators, dot segments and control characters are rejected; URL parameter values are encoded and validated | Adversarial regex review and performance tests |
| RTE-03 | 405 + Allow, automatic OPTIONS, explicit HEAD and HEAD-to-GET fallback; shared Kernel suppresses HEAD bodies after global middleware | FPM/Swoole integration parity and protocol-level verification |
| RTE-04 | Unique route names, relative/absolute URL generation and query-string parameters | Request-context absolute URL convenience is intentionally not implicit |
| RTE-05 | Nested prefix/middleware restoration and attribute middleware composition | Concurrent bootstrap/registration boundary and inherited/repeated attribute tests |
| RTE-06 | resource/apiResource route registrar, only/except selectors, explicit parameter names and PUT/PATCH update endpoints | Full route matrix and compatibility tests |
| RTE-07 | One explicit fallback route, dispatched only after no path/method match | FPM/Swoole integration and fallback exception coverage |
| RTE-08 | route:list now reports name, handler, middleware and constraints | CLI output regression tests and optional machine-readable format |
| RTE-09 | Added unit tests for matching, encoded input, groups, request-local matched route clones and optional Swoole coroutine interleaving | Swoole concurrency/security stress tests |
| RTE-10 | Added `benchmarks/routing.php` for static hits, last dynamic hits, misses, and static/dynamic method negotiation at configurable route-table sizes | Execute on a stable local machine, record p50/p95/p99, throughput, bootstrap time and memory; compare before/after any index optimization |

### v3 routing compatibility findings

The tracked v3 routing source was inspected directly and the detailed findings are recorded in [V3_API_INVENTORY.md](V3_API_INVENTORY.md). Key behavior differences that must remain explicit:

- v3 maps `/` to `/index`; v4 intentionally keeps `/` as the root route.
- v3 route storage is keyed by path only, so a second HTTP method on the same path overwrites the first; v4 uses method-specific registries.
- v3 supports GET/POST/PUT/DELETE and Get/Post/Put/Delete attributes. v4 adds PATCH/OPTIONS/HEAD/WS.
- v3 group middleware and prefix stacks are static and not protected by `finally`; v4 restores nested registration state in `finally`.
- v3 controller discovery instantiates controllers and reflects methods during registration; v4 discovers attributes during bootstrap and resolves controllers during dispatch.
- v3 has no named route URL generation, constraints, 405/Allow, HEAD/OPTIONS negotiation, fallback or resource route helper.
- v3 middleware rejection and invalid aliases can echo/terminate; v4 uses response-based middleware and fail-closed behavior.

## 3. Developer-facing API direction

Keep the existing API working:

```php
use App\\Support\\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::prefix('/api/v1', function (): void {
    Route::middleware(['api-auth'], function (): void {
        Route::get('/users/{id}', [UserController::class, 'show']);
    });
});
```

Add capabilities in a fluent, discoverable way where possible:

```php
// Named route + URL generation
Route::get('/users/{user}', [UserController::class, 'show'])
    ->name('users.show');

// Parameter constraints (compiled once)
Route::get('/orders/{id}', [OrderController::class, 'show'])
    ->whereNumber('id');

// Resource route customization
Route::apiResource('/users', UserController::class)
    ->only(['index', 'show', 'store']);

// Explicit fallback
Route::fallback(NotFoundController::class);
```

The examples above describe the target API, not currently available methods. Final signatures must be aligned with existing conventions and documented before implementation. Avoid magic conventions that cannot be inspected via `route:list`.

## 4. Task breakdown and acceptance criteria

### RTE-00 — Baseline, compatibility contract and test inventory
- [ ] Inspect all v3 routing classes, route attributes, provider matching, middleware behavior and README examples.
- [ ] Inspect all v4 routing, dispatcher, middleware resolver, route:list command and existing routing tests.
- [ ] Build a v3-to-v4 behavior table covering method, path, handler, parameters, middleware order, route registration order, duplicate definitions, 404 and exceptions.
- [ ] Identify every consumer of `Route::match()`, Route properties and route metadata before changing signatures.
- [ ] Record existing behavior that must remain backward-compatible.
**Acceptance:** no route API or return contract is changed without identifying callers and adding a migration note.

### RTE-01 — Route definition validation and deterministic registration
- [ ] Validate HTTP methods and reject unsupported/empty methods at registration.
- [ ] Normalize paths consistently; define behavior for repeated slashes, trailing slash, query strings, percent-encoding and root path.
- [ ] Reject malformed placeholder names, duplicate parameter names and malformed patterns early.
- [ ] Define duplicate route policy (same method + same normalized path): fail fast by default or provide an explicit replacement mechanism; never silently overwrite an unrelated route.
- [ ] Define deterministic static/dynamic precedence and overlapping dynamic-route behavior.
- [ ] Ensure route metadata is immutable after bootstrap except for safe registration-time fluent configuration.
**Acceptance:** malformed definitions fail during bootstrap with actionable exceptions; deterministic matching tests pass.

### RTE-02 — Parameter constraints and safe pattern compilation
- [ ] Add a small set of common constraints (numeric, alphabetic, alphanumeric, UUID) and a generic constrained parameter API if it can be implemented safely.
- [ ] Compile every constraint exactly once at registration/bootstrap.
- [ ] Anchor patterns and prevent accidental delimiter/modifier injection.
- [ ] Validate constraint syntax and reject invalid or catastrophic patterns where feasible; document that user-defined regex must be trusted configuration.
- [ ] Define encoded slash, Unicode and parameter decoding semantics; never allow a decoded slash to change route segmentation.
- [ ] Preserve named parameter values and support positional controller injection only if existing dispatcher contract requires it.
**Acceptance:** valid/invalid constraint tests, encoded-path tests, Unicode tests and adversarial-pattern tests pass; no regex compilation occurs in the request hot path.

### RTE-03 — HTTP method semantics (405, HEAD and OPTIONS)
- [ ] Distinguish “path does not exist” (404) from “path exists for another method” (405).
- [ ] Return an accurate `Allow` header for 405 responses.
- [ ] Implement HEAD fallback to GET while suppressing the response body at the HTTP emitter layer; do not execute a separate duplicate handler.
- [ ] Define automatic OPTIONS behavior and interaction with explicitly registered OPTIONS routes and CORS middleware.
- [ ] Ensure WS routes do not leak into ordinary HTTP method negotiation.
**Acceptance:** tests cover 404 vs 405, Allow values, HEAD status/headers/body suppression, explicit/automatic OPTIONS, and FPM/Swoole parity.

### RTE-04 — Named routes and URL generation
- [ ] Add unique route names and a fluent `name()` registration API.
- [ ] Add a URL generator/helper that resolves named routes with required parameters.
- [ ] Encode parameter values safely and append optional query parameters without string concatenation vulnerabilities.
- [ ] Fail clearly for unknown names, missing parameters and duplicate names.
- [ ] Support absolute URLs only through an explicit option using validated configured/request URL context; relative URLs remain the default.
- [ ] Ensure route names and metadata are exposed by `route:list`.
**Acceptance:** tests cover required/missing/extra parameters, reserved characters, query strings, duplicate names, relative URLs and configured absolute URLs.

### RTE-05 — Group, middleware and attribute consistency
- [ ] Specify and test nested prefix composition and restoration after exceptions.
- [ ] Specify middleware order (outer group → inner group → route → attribute-specific middleware) and prevent duplicate accidental application.
- [ ] Ensure group metadata is copied into route definitions at registration; no mutable global group stack is read during requests.
- [ ] Support controller-level group metadata only if it can be expressed without reflection in the request path.
- [ ] Validate attribute method/path/middleware at bootstrap and report controller + method in errors.
- [ ] Test repeated attributes, inherited methods, static methods and duplicate route declarations.
**Acceptance:** nested group tests and middleware execution-order tests pass in both runtimes; attribute reflection remains bootstrap-only.

### RTE-06 — Resource route ergonomics
- [ ] Preserve existing `resource()` behavior unless a compatibility review proves it incorrect.
- [ ] Add `apiResource()` without HTML create/edit routes.
- [ ] Add `only()` / `except()` selection and predictable route names.
- [ ] Allow explicit parameter-name mapping where useful; do not infer database model binding.
- [ ] Ensure generated routes are visible and auditable through `route:list`.
**Acceptance:** exact route matrix tests for resource/apiResource, only/except and custom parameter names.

### RTE-07 — Fallback route and 404 customization
- [ ] Add one explicit fallback route, registered separately from ordinary routes.
- [ ] Ensure fallback executes only after normal matching and method negotiation.
- [ ] Define whether fallback receives the unmatched path/method through Request context, not mutable Router globals.
- [ ] Keep default 404 behavior unchanged when no fallback is registered.
**Acceptance:** fallback precedence, method behavior, request isolation and default 404 tests pass.

### RTE-08 — Route introspection and diagnostics
- [ ] Extend `route:list` with method, URI, name, handler, middleware and parameter constraints.
- [ ] Add stable sorting/grouping and machine-readable output only if the CLI conventions support it.
- [ ] Include duplicate/overlap warnings where statically detectable.
- [ ] Ensure introspection uses already registered metadata and does not trigger reflection or service construction.
**Acceptance:** CLI tests assert columns, sorting, filters/format and that listing routes does not instantiate controllers.

### RTE-09 — Routing correctness, security and runtime isolation
- [ ] Test method/path case behavior, trailing slash policy, query string stripping, percent-encoding, Unicode and malformed paths.
- [ ] Test route parameter isolation across sequential and concurrent Swoole requests.
- [ ] Test route registry immutability after bootstrap and ensure no matched params are written to shared route definitions.
- [ ] Test middleware/handler exceptions do not corrupt later requests or static registration stacks.
- [ ] Test static route lookup and dynamic route matching against large route tables.
- [ ] Verify no reflection, regex compilation, filesystem reads or container construction occurs in the match hot path.
**Acceptance:** security/regression suite passes; concurrent requests never share route params or group state.

### RTE-10 — Performance baseline and release certification
- [ ] Add reproducible benchmarks for static hits, dynamic hits, misses, 404/405 negotiation and route tables of representative sizes (e.g. 100, 1,000 and 10,000 routes).
- [ ] Report throughput and p50/p95/p99 latency where the runner permits; measure memory/RSS and bootstrap cost separately.
- [ ] Compare baseline before/after each routing feature; reject complexity that causes material regression without a clear benefit.
- [ ] Run full PHPUnit and FPM/Swoole routing integration tests once the phase implementation is complete.
- [ ] Update README, API docs, migration matrix, checklist, roadmap and progress log.
**Acceptance:** benchmark methodology and results are committed; all relevant tests pass; documented support boundaries match implementation.

## 5. Priority and sequencing

| Priority | Tasks | Reason |
|---|---|---|
| P0 — correctness first | RTE-00, RTE-01, RTE-03, RTE-09 | Prevent ambiguous routes, incorrect HTTP semantics and request-state leakage |
| P1 — professional daily use | RTE-02, RTE-04, RTE-05, RTE-08 | Constraints, named URLs, predictable middleware and observability are common in complex apps |
| P2 — productivity | RTE-06, RTE-07 | REST resource ergonomics and explicit fallback handling |
| P3 — certification | RTE-10 | Measure the actual hot path and certify the completed phase |

Dependencies: RTE-00 precedes all implementation. RTE-01 precedes RTE-02/RTE-03. RTE-04 and RTE-06 depend on stable route metadata. RTE-10 runs after implementation and tests stabilize.

## 6. Performance and architecture rules

- Static route match should remain a direct hash lookup: expected O(1).
- Dynamic route patterns are compiled once at registration. No regex building or reflection per request.
- Keep the common path allocation-light; do not instantiate controllers during matching.
- Consider a first-segment/prefix index for dynamic routes only if benchmarks show linear scanning is a bottleneck. Do not add a trie/prefix tree speculatively.
- Do not sort all dynamic routes on every request. Resolve precedence once at registration.
- Do not store matched parameters on shared route definitions. Return request-specific match data.
- Avoid process-global mutable route/group state during request execution.
- Keep route cache optional and deferred until cold-start profiling proves value.
- Avoid implicit model binding and other hidden database work in the router.
- Keep middleware resolution/instantiation in the dispatcher/container path, with request-scoped dependencies where needed.
- Any regex constraint is compiled and validated once; route definitions are trusted bootstrap configuration, not user input.
- Optimize for Swoole persistent workers without introducing behavior differences in FPM.

## 7. Completion definition

This phase is complete only when:
1. Every RTE task has a recorded outcome (implemented, intentionally deferred, or not needed with evidence).
2. All existing route APIs have compatibility tests or documented migration changes.
3. 404/405/HEAD/OPTIONS behavior is consistent across FPM and Swoole.
4. Named routes, URL generation and constraints have clear tests and documented APIs.
5. Group/middleware/attribute registration is deterministic and request-safe.
6. `route:list` accurately reflects effective route definitions.
7. Benchmarks show no unexplained regression in the common static/dynamic match paths.
8. README, API docs, migration matrix, checklist, roadmap and progress log agree.

## 8. Explicitly deferred candidates

These may be considered after this phase, only with a concrete application use case:
- Host/subdomain route matching for multi-tenant or multi-domain apps.
- Route version negotiation beyond conventional `/api/v1` prefix groups.
- Route caching/serialized route maps for cold-start optimization.
- Automatic controller parameter/model binding.
- Route-level rate-limit DSL (prefer middleware).
- Locale-aware route matching.
- Route annotations beyond PHP attributes.

Do not treat deferred candidates as missing core requirements.


## Benchmark usage

Run the committed synthetic benchmark after installing Composer dependencies:

```bash
php benchmarks/routing.php --iterations=1000 --sizes=100,1000,10000
```

The script reports static hit, last dynamic hit, not-found, full HTTP 404/405 dispatch and static/dynamic method-negotiation latency percentiles, throughput, bootstrap time and route-table memory delta. Run multiple times on an otherwise idle machine using the same PHP build. Do not use synthetic results as production capacity claims.


## 2026-11-05 — Post-implementation compatibility audit

- Re-audited the current Router, Route, RequestDispatcher, Kernel, Route DSL, resource registrar, RouteMatcher, route-list command and application route declarations.
- Found that the legacy list-based `RouteMatcher` facade rebuilt route definitions without carrying over constraints, names or middleware. It now preserves those registration-time properties when adapting definitions to the current Router.
- Hardened encoded path-segment validation against recursively encoded slash, backslash, dot-segment and control-byte sequences. The same guard is applied to incoming paths, decoded dynamic parameters and named URL parameter values.
- Added regression cases for triple-encoded separators, encoded backslashes, recursively encoded URL parameters and legacy RouteMatcher metadata preservation.
- The test file now contains 42 routing semantics test methods. This is a source-level count only; PHPUnit has not been executed.
- Remaining release blockers: full PHPUnit run, FPM/Swoole integration parity, Swoole concurrency stress, complete v3 call-site compatibility review and measured benchmark results. No performance index/trie has been added without benchmark evidence.


### Benchmark coverage refinement

The routing benchmark now measures both low-level Router matching and full `RequestDispatcher` handling for HTTP 404 and 405 responses. Percentiles use nearest-rank indexing. The benchmark remains unexecuted, so no latency, throughput or memory claims are made.


- Added an explicit UTF-8 route-parameter round-trip regression test (`José`), covering percent-decoding during matching and RFC 3986 URL generation.


- Added a `route:list` CLI-output regression test asserting the diagnostic columns and a registered application route are present. It has been added but not executed.
