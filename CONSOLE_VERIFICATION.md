# Console Verification Notes

This file preserves historical verification context. The v4 CLI has evolved beyond the original v3-era `php console` examples; use the current `php ds` entry point and command list.

## Current v4 entry point

```bash
php ds
php ds route:list
php ds test
php ds migrate --status
php ds config
```

The current command registration is defined by `core/Console/ConsoleKernel.php`. The v4 command set includes Swoole/FPM serving, domain/controller/model/view/test/migration generators, route listing, config handling, PHPUnit execution and database migrations.

## Historical note

The earlier console implementation was tested with `php console` and `make:domain`. Those commands and paths are retained here only as historical context; do not use this document as the authoritative v4 command reference.

## Verification policy

A command should be called verified only when a current v4 GitHub Actions job or a reproducible test has executed the exact command on the target branch. For current status, consult the CI workflows and [DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md).
