<?php

declare(strict_types=1);

namespace Ds\Foundations\Connection\Models;

/**
 * Compatibility name for the v3 model base. It now uses the v4 PDO query
 * builder; legacy magic property hydration and relationship APIs are not included.
 */
abstract class DsModel extends \Ds\Database\Model
{
}
