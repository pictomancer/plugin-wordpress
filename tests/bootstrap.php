<?php

declare(strict_types=1);

// The plugin source files guard against direct access with ABSPATH; define it
// so they can be loaded under PHPUnit without a full WordPress runtime.
if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../inc/class-pictomancer-client.php';
require_once __DIR__ . '/../inc/class-pictomancer-optimizer-service.php';
require_once __DIR__ . '/../inc/class-pictomancer-stats.php';
