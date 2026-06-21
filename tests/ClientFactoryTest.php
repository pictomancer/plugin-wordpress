<?php

declare(strict_types=1);

namespace Pictomancer\WP\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Pictomancer_Client_Factory;

final class ClientFactoryTest extends TestCase
{
    public function testEmptyBaseUrlFallsBackToDefault(): void
    {
        $this->assertSame('https://api.pictomancer.ai', Pictomancer_Client_Factory::normalize_base_url(''));
    }

    public function testTrailingSlashStripped(): void
    {
        $this->assertSame('https://api.pictomancer.ai', Pictomancer_Client_Factory::normalize_base_url('https://api.pictomancer.ai/'));
    }

    public function testLegacyV1SuffixStripped(): void
    {
        $this->assertSame('https://api.pictomancer.ai', Pictomancer_Client_Factory::normalize_base_url('https://api.pictomancer.ai/v1'));
    }

    public function testLegacyV1WithTrailingSlashStripped(): void
    {
        $this->assertSame('https://api.pictomancer.ai', Pictomancer_Client_Factory::normalize_base_url('https://api.pictomancer.ai/v1/'));
    }

    public function testCustomHostPreserved(): void
    {
        $this->assertSame('https://images.acme.test', Pictomancer_Client_Factory::normalize_base_url('https://images.acme.test'));
    }

    public function testCreateUsesApiKeyAndNormalizedBase(): void
    {
        $client = Pictomancer_Client_Factory::create([
            'api_key' => 'sk-123',
            'api_url' => 'https://api.pictomancer.ai/v1',
        ]);

        // The client is opaque; assert it constructs without error and is the SDK type.
        $this->assertInstanceOf(\Pictomancer\Client::class, $client);
    }

    public function testResolveApiKeyUsesOptionWithoutConstant(): void
    {
        $this->assertSame('sk-opt', Pictomancer_Client_Factory::resolve_api_key(['api_key' => 'sk-opt']));
    }

    public function testResolveApiKeyEmptyWhenMissing(): void
    {
        $this->assertSame('', Pictomancer_Client_Factory::resolve_api_key([]));
    }

    public function testResolveApiUrlUsesOptionWithoutConstant(): void
    {
        $this->assertSame('https://images.acme.test', Pictomancer_Client_Factory::resolve_api_url(['api_url' => 'https://images.acme.test']));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstantApiKeyOverridesOption(): void
    {
        define('PICTOMANCER_API_KEY', 'sk-from-constant');

        $this->assertSame('sk-from-constant', Pictomancer_Client_Factory::resolve_api_key(['api_key' => 'sk-opt']));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testConstantApiUrlOverridesOption(): void
    {
        define('PICTOMANCER_API_URL', 'https://gateway.internal');

        $this->assertSame('https://gateway.internal', Pictomancer_Client_Factory::resolve_api_url(['api_url' => 'https://images.acme.test']));
    }
}
