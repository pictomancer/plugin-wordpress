<?php

declare(strict_types=1);

namespace Pictomancer\WP\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Pictomancer;

/**
 * Guards the plugin's load order. WordPress loads pluggable.php (wp_hash and
 * friends) only after every plugin file, so the plugin must not call pluggable
 * functions - nor instantiate anything that does - at file scope. Each test
 * loads the plugin in a fresh process with only the load-time WordPress
 * functions stubbed, exactly as the real request does.
 */
final class PluginBootstrapTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoadingPluginWithoutPluggableFunctionsDoesNotFatal(): void
    {
        require __DIR__ . '/wp-load-stubs.php';
        require dirname(__DIR__) . '/pictomancer-image-optimizer.php';

        $this->assertArrayHasKey('plugins_loaded', $GLOBALS['pictomancer_test_hooks']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoadingPluginDefersConstructionUntilPluginsLoaded(): void
    {
        require __DIR__ . '/wp-load-stubs.php';
        require dirname(__DIR__) . '/pictomancer-image-optimizer.php';

        // Loading the file registers the boot hook and nothing else: no
        // collaborator has been constructed, so none of their hooks exist yet.
        $this->assertSame([ 'plugins_loaded' ], array_keys($GLOBALS['pictomancer_test_hooks']));

        foreach ($GLOBALS['pictomancer_test_hooks']['plugins_loaded'] as $callback) {
            $callback();
        }

        $registered = array_keys($GLOBALS['pictomancer_test_hooks']);
        $this->assertContains('wp_generate_attachment_metadata', $registered);
        $this->assertContains('rest_api_init', $registered);
        $this->assertContains('admin_menu', $registered);
        $this->assertInstanceOf(Pictomancer::class, Pictomancer::get_instance());
    }
}
