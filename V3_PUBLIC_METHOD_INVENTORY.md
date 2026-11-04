# v3 Public/Extension Method Inventory

Repository: `Daevsoft/framework`  
Source ref: `v3`  
Purpose: declaration-level inventory to drive method-by-method v4 compatibility review.

## Scope and interpretation

A source scan was performed across all **76 tracked PHP files** in the v3 tree. The lists below record public methods and selected protected extension points declared in the principal public-facing classes. Internal/private methods, inherited methods, PHP magic behavior beyond the listed methods, global constants, config keys and exact runtime semantics still require separate verification. A method with a similar v4 name is not evidence of behavioral compatibility.

## Core, providers and console

| v3 class/file | Public/protected surface observed | v4 mapping / review status |
|---|---|---|
| `Core\\Application` | `boot()`, `shutdown()` | v4 bootstrap/kernel/lifecycle; lifecycle semantics and provider compatibility need tests |
| `Core\\Ds` | `__construct($autoRunProviders)`, `connect()`, `getProvider($providerName)`, `isDebug()` | v4 application/container/bootstrap; no drop-in facade parity established |
| `Foundations\\Provider` | `install()`, `run($param)` | v4 provider/bootstrap conventions differ; adapter coverage open |
| `Commands\\Console` | static `write($text, $color)`, `writeln($text, $color)` | v4 Console output/command APIs differ; compatibility not established |
| `Commands\\Terminal` | `__construct($argv)`, `start()` | v4 ConsoleKernel; command names, parsing and exit codes differ |
| `Commands\\Runner` | `__construct($options)`, `setup()`, `run()` | v4 PHPUnit runner; custom Runner behavior is not drop-in |
| `Commands\\EnvGenerator` | `run()` | v4 config command is not confirmed to preserve interactive generation |
| `Commands\\CronJob\\Job` | `run()`, protected `start()` | v4 Task/Scheduler are different APIs |
| `Commands\\CronJob\\Worker` | `run()` | v4 has Swoole task-worker dispatch, not the v3 file-discovery runner |
| `Commands\\Generator\\AddFile` | `run()` | v4 individual generators replace this command; output and options need parity checks |
| `Commands\\Tester\\Assert` | `__construct($condition)`, `printResult()`, `commit()`, static `check()`, `equal()`, `notEqual()`, `greaterThan()`, `lowerThan()` | v4 PHPUnit assertions; no custom Assert API adapter |
| `Commands\\Tester\\Tester` | static `passIncrease()`, `failIncrease()`, `run()` | v4 PHPUnit; no custom Tester compatibility |
| `Commands\\Tester\\TesterFunc` | global `describe()`, `mock()` | v4 PHPUnit test structure; global helper parity not established |

## Common utilities and collections

| v3 class/file | Public surface observed | v4 mapping / review status |
|---|---|---|
| `Common\\Collection` | `__construct`, iterator methods `current/key/next/rewind/valid`, ArrayAccess `offsetSet/offsetExists/offsetUnset/offsetGet`; `add/collapse/crossJoin/divide/dot/undot/except/exists/first/last/take/flatten/forget/get/has/hasAny/isAssoc/isList/keyBy/prependKeysWith/only/pluck/map/mapWithKeys/mapSpread/prepend/pull/random/set/shuffle/sort/sortDesc/sortRecursive/sortRecursiveDesc/toCssClasses/toCssStyles/whereNotNull/wrap/toArray/toJson/serialize/jsonSerialize/dump`; magic `__toString/__sleep/__serialize/__get/__debugInfo` | Current v4 legacy collection includes the inventoried helpers; nested mutation, null-valued pull, serialization round-trip and magic property/debug access have focused regression coverage. Callback edge cases and inherited ArrayIterator parity remain open |
| `Common\\File` | `__construct($path,$mode)`, `close()`, `rewrite()`, `append()`, `create()`, `delete()`, `getContent()`, `getFilename()` | v4 filesystem utilities differ; no complete adapter |
| `Common\\Cache` | `run()`, `clearAllPages()`, `resetCacheRecord()`, `clearReferences()` | v4 FileCache/view cache replaces implementation; invalidation semantics need comparison |
| `Common\\Func` | static `check()`, `isSlash()`, `replaceSlash()` | v4 helpers; method/global compatibility not fully tested |
| `Config\\Env` | static `get($key,$default)` | v4 env/config helpers; precedence and coercion need parity tests |
| `Config\\AppEnv` | `__construct($envfile)`, `generateConfig()` | v4 config cache/generator differs |

## Database, query builder and model

| v3 class/file | Public surface observed | v4 mapping / review status |
|---|---|---|
| `Connection\\Db` | `__construct()`, `init()`, `getConnection()`, `addParameter()`, `query()`, `select()`, `distinct()`, `getQueryType()`, `getSelectedColumns()`, `copyProperties()`, `getParameters()`, `and()`, `where()`, `isNull()`, `orIsNull()`, `isNotNull()`, `orIsNotNull()`, `or()`, `whereIn()`, `orWhereIn()`, `getQuery()`, `orderBy()`, `asc()`, `desc()`, `limit()`, `join()`, `alias()`, `leftJoin()`, `rightJoin()`, `attachParent()`, `copy()`, `groupBy()`, `having()`, `generateQuery()`, `clear()`, `exist()`, `insert()`, `bulkInsertObject()`, `bulkInsertArray()`, `update()`, `delete()`, `getLastId()`, `customExecute()`, `execute()`, `readAll()`, `read()`, static `raw()`, `get_all()`, `count()`, `get()`, `first()`, `get_array()`, `get_object()`, `get_assoc()`, `get_bound()`, `get_into()`, `get_lazy()`, `get_named()`, `get_single_array()`, `get_row_array()`, `get_row_object()`, `get_row_assoc()`, `get_row_bound()`, `get_row_into()`, `get_row_lazy()`, `get_row_named()`, `get_row()`, `get_exist()` | v4 PDO QueryBuilder/DatabaseManager implement a different fluent API and result contract. Existing join/subquery/grouped-count work does not prove complete return-mode or alias parity |
| `Connection\\DsModel` | `__construct()`, static `_select()`, protected `select()`, `query()`, `getQuery()`, `distinct()`, `groupBy()`, `asc()`, `limit()`, `desc()`, `join()`, `leftJoin()`, `having()`, `and()`, `or()`, protected `where()`, `whereIn()`, `__call()`, static `__callStatic()`, `like()`, `orLike()`, `equal()`, `not_equal()`, `insert()`, `insert_bulk_array()`, `insert_bulk()`, `update()`, `delete()`, static `all()`, `get_object()`, `get_array()`, `row()`, `first()`, static `initiateClass()`, `last()`, static `find()`, `findBy()`, `findIsNull()`, `findIsNotNull()`, `findsBy()`, `findsWhere()`, `findsIsNull()`, `findsIsNotNull()`, protected `exist()`, static `save()`, `filledFields()`, static `remove()`, protected `paginate()`, `validate()`, iterator `key/next/rewind/count/valid`, `__toString()` | v4 DsModel adapter now covers select-chain entry points, comparison-aware where/orWhere, common ordering/grouping/distinct/joins, direct/query result accessors, request-aware protected pagination and selected CRUD/filter methods; stateful result objects, dynamic magic dispatch, iterator and full bulk-write semantics remain incomplete |
| `Connection\\SqlModel` | `__construct()`, `insert()`, `update()`, `delete()`, `where()`, `whereRaw()`, `whereIn()`, `orWhereIn()`, `orWhereRaw()`, `orWhere()`, `addValue()`, `addRawValue()`, `setValue()`, `setRawValue()`, `execute()`, `bulkInsert()` | v4 query builder replaces this SQL mutation builder; no full facade parity |
| `Connection\\DatabaseProvider` | `install()`, `run($param)`, static `getConnection()` | v4 DatabaseManager; provider lifecycle and static accessor parity open |
| `Connection\\QueryCommon` | `WrapQuot()`, `setupProvider()` plus protected type/bind helpers | v4 identifier/binding implementation differs |
| `Connection\\SqlOperator` | static `setOperand()` | v4 internal SQL operator helpers differ |
| `Connection\\Models\\Enumerable` | `validate()` | v4 model/collection contracts differ |

## HTTP, routing and security

| v3 class/file | Public surface observed | v4 mapping / review status |
|---|---|---|
| `Network\\Request` | `validated()`, `all()`, static `getBack()`, `getReferrer()`, `trackRoute()`, `getTrackRoute()`, `json()`, magic `__get()`, `add()`, `old()`, `setOld()`, `files()`, `file()` | v4 Request covers input/JSON/validation/per-field old-input and normalized single/multi-file UploadedFile metadata with explicit secure storage; route tracking, back/referrer and exact validated() return semantics remain open. Upload auto-move behavior intentionally changed to explicit store() |
| `Network\\Response` | `__construct()`, static `header()`, `applyToSwoole()`, `applyToPhp()`, `reset()` | v4 immutable Response/Emitter differs; static header state and reset behavior not drop-in |
| `Network\\Middleware` | `handle(Request,$next)` | v4 MiddlewareInterface/pipeline exists; parameter and return behavior need migration tests |
| `Network\\Validator` | `__construct()`, static `make()`, `doValidate()` | v4 Validator has common rules; result/message/custom-rule parity incomplete |
| `Routing\\Route` | static `get/post/put/delete/middleware/group` | v4 Route facade adds verbs/prefix/resource/ws; grouping semantics and route metadata need comparison |
| `Routing\\BaseRoute` / `RouteData` | `middleware()`, route metadata constructor | v4 Route model differs |
| `Routing\\RouteProvider` | static `addRoute()`, `assignMiddleware()`; `install()`, `run()`, `findRoute()`, `validateMiddleware()`, `executeRoute()`, `response()` | v4 Router/RequestDispatcher reimplemented; route matching and response reflection require edge-case parity |
| `Security\\Csrf` | static `verify()`, `token()`, `generate()` | v4 CSRF middleware/helpers differ; token storage/rotation semantics need comparison |

## Sessions, validation, views and global helpers

| v3 class/file | Public surface observed | v4 mapping / review status |
|---|---|---|
| `Session\\Session` | static `user()`, `destroy()` | v4 session facade is partial; user serialization and destruction semantics need review |
| `Session\\SessionManager` | static `init()`; `get()`, `put()`, `has()`, `delete()`, `destroy()`, `id()` | v4 SessionManager exists; storage, cookie, regeneration and concurrency semantics need parity tests |
| `Validator\\ValidationProvider` | static `register()`, `boot()`, `validate()`; `install()`, `run()` | v4 legacy namespace adapter now supports registered ValidationRule callbacks and required/min/max; provider lifecycle install/run remain intentionally omitted |
| `Validator\\ValidationRule` | `__construct()`, `runValidation()` | v4 compatibility ValidationRule preserves the callback contract using Closure::fromCallable and typed result objects |
| `Validator\\ValidationResult` | `__construct($isValid,$error)` | v4 compatibility ValidationResult preserves isValid/error properties |
| `Validator\\Validator` | `__construct()`, static `make()`, `doValidate()`, static `oldValue()` | v4 compatibility adapter exposes make/doValidate/passes/fails/errors/validated/validate/oldValue; invalid input is reported via ValidationException rather than response termination |
| `View\\View` | static `contents()`, `filename()` | v4 view facade is partial adapter |
| `View\\PageProvider` | static `getInstance()`, `init()`, `page_not_found()`; `install()`, `run()`, `viewFileName()`, `__page()`, `pie_view()`, `not_found()` | v4 ViewRenderer/PieCompiler replace provider rendering; layout/slot/import/component/cache paths are covered. Legacy PageProvider is not a drop-in adapter because v3 component rendering contains dd()/debug termination and provider lifecycle hooks are not retained |
| `View\\Slot` | `__construct()`, `attachSlot()`, `getSlot()` | Replaced by compiler-managed named/default slots and caller-scoped closures; the v3 class's undeclared static slot store and inverted lookup branch are intentionally not copied |
| `View\\CacheView` | `checkRootUpdated()`, `getEncryptedFileName()`, `recordViewTime()`, `__construct()`, `is_modified()`, `exists()`, `recordToFile()` | v4 compiled-view cache differs |
| `Helper\\Function.php` | global `asset()`, `view()`, `session()`, `unsession()`, `flash()`, `is_flash()`, `set_flash()`, `old()`, `js_source()`, `app_url()` | v4 helper set includes asset/view/session/flash/old/js_source/app_url; view now returns rendered content consistently for both Swoole and FPM, and app_url prefers validated APP_URL then request-aware host context |
| `Helper\\Date` | static `timestamp()` | v4 `Ds\\Helper\\Date::timestamp()` adapter returns Unix seconds |
| `Helper\\Str` | static `contains()`, `replace()`, `empty()`, `nullEmpty()` | v4 `Ds\\Helper\\Str` adapter provides case-insensitive contains/replace and strict empty/nullEmpty semantics |

## Compatibility completion criteria

For each public method above, mark a v4 disposition only after checking:

1. Same or intentionally migrated method name and callable surface.
2. Parameter names/order/defaults/types and accepted input shapes.
3. Return type, result shape, key preservation and mutation behavior.
4. Exceptions, error messages/statuses and failure behavior.
5. Side effects, request/worker lifetime, database transactions and cleanup.
6. A regression test that exercises both the expected behavior and important edge cases.

This declaration inventory is a much broader baseline than the earlier file-level matrix, but it is **not yet a completed parity audit**. The next work is to map every listed method to v4 source, classify it as compatible/changed/missing, and add focused tests for the high-impact database, request, session, view and helper surfaces.
