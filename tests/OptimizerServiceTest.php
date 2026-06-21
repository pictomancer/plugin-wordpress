<?php

declare(strict_types=1);

namespace Pictomancer\WP\Tests;

use Pictomancer\Client;
use Pictomancer\Response;
use PHPUnit\Framework\TestCase;
use Pictomancer_Optimizer_Service;

final class OptimizerServiceTest extends TestCase
{
    private const JPEG_BYTES = "\xFF\xD8\xFFoptimized";
    private const RAW = 'rawimagebytes';

    private function newService(RecordingTransport $transport, int $quality = 0, string $format = ''): Pictomancer_Optimizer_Service
    {
        $client = new Client('k', Client::DEFAULT_BASE_URL, 30.0, $transport);

        return new Pictomancer_Optimizer_Service($client, $quality, $format);
    }

    private function imageResponse(string $bytes): Response
    {
        return new Response(200, ['content-type' => 'image/jpeg'], $bytes);
    }

    public function testReturnsOptimizedBytes(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport);

        $out = $service->optimize_bytes(self::RAW, 'image/jpeg');

        $this->assertSame(self::JPEG_BYTES, $out);
    }

    public function testSendsSourceAsDataUri(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport);

        $service->optimize_bytes(self::RAW, 'image/png');

        $expected = 'data:image/png;base64,' . base64_encode(self::RAW);
        $this->assertSame($expected, $transport->lastBody()['source']);
    }

    public function testStripIsAlwaysRequested(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport);

        $service->optimize_bytes(self::RAW, 'image/jpeg');

        $this->assertTrue($transport->lastBody()['strip']);
    }

    public function testQualityForwardedWhenSet(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport, quality: 80);

        $service->optimize_bytes(self::RAW, 'image/jpeg');

        $this->assertSame(80, $transport->lastBody()['q']);
    }

    public function testQualityOmittedWhenZero(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport, quality: 0);

        $service->optimize_bytes(self::RAW, 'image/jpeg');

        $this->assertArrayNotHasKey('q', $transport->lastBody());
    }

    public function testOutputFormatForwardedWhenSet(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport, format: 'webp');

        $service->optimize_bytes(self::RAW, 'image/png');

        $this->assertSame('webp', $transport->lastBody()['format']);
    }

    public function testFormatOmittedWhenKeepingOriginal(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport, format: '');

        $service->optimize_bytes(self::RAW, 'image/png');

        $this->assertArrayNotHasKey('format', $transport->lastBody());
    }

    /** @return array<string, mixed> */
    private function newMetadata(): array
    {
        return [
            'file' => '2026/06/photo.jpg',
            'original_image' => 'photo-pristine.jpg',
            'sizes' => [
                'thumbnail' => ['file' => 'photo-150x150.jpg', 'mime-type' => 'image/jpeg'],
                'medium' => ['file' => 'photo-300x200.jpg', 'mime-type' => 'image/jpeg'],
            ],
        ];
    }

    public function testPlanIncludesOriginalAndAllSizes(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));

        $plan = $service->files_to_optimize($this->newMetadata(), '/uploads', 'image/jpeg', false, true);

        $this->assertSame([
            ['path' => '/uploads/2026/06/photo.jpg', 'mime' => 'image/jpeg', 'kind' => 'original'],
            ['path' => '/uploads/2026/06/photo-150x150.jpg', 'mime' => 'image/jpeg', 'kind' => 'size'],
            ['path' => '/uploads/2026/06/photo-300x200.jpg', 'mime' => 'image/jpeg', 'kind' => 'size'],
        ], $plan);
    }

    public function testPlanSkipsOriginalWhenAlreadyOptimized(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));

        $plan = $service->files_to_optimize($this->newMetadata(), '/uploads', 'image/jpeg', true, true);

        $this->assertSame(['size', 'size'], array_column($plan, 'kind'));
    }

    public function testPlanExcludesSizesWhenDisabled(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));

        $plan = $service->files_to_optimize($this->newMetadata(), '/uploads', 'image/jpeg', false, false);

        $this->assertSame([
            ['path' => '/uploads/2026/06/photo.jpg', 'mime' => 'image/jpeg', 'kind' => 'original'],
        ], $plan);
    }

    public function testPlanNeverIncludesPristineOriginalImage(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));

        $plan = $service->files_to_optimize($this->newMetadata(), '/uploads', 'image/jpeg', false, true);

        $this->assertNotContains('/uploads/2026/06/photo-pristine.jpg', array_column($plan, 'path'));
    }

    public function testPlanDedupesRepeatedPaths(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));
        $metadata = [
            'file' => '2026/06/photo.jpg',
            'sizes' => [
                'full' => ['file' => 'photo.jpg', 'mime-type' => 'image/jpeg'],
            ],
        ];

        $plan = $service->files_to_optimize($metadata, '/uploads', 'image/jpeg', false, true);

        $this->assertCount(1, $plan);
    }

    public function testPlanUsesSizeMimeTypeWhenPresent(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));
        $metadata = [
            'file' => '2026/06/photo.png',
            'sizes' => [
                'thumbnail' => ['file' => 'photo-150x150.webp', 'mime-type' => 'image/webp'],
            ],
        ];

        $plan = $service->files_to_optimize($metadata, '/uploads', 'image/png', false, true);

        $this->assertSame('image/webp', $plan[1]['mime']);
    }

    public function testPlanEmptyWithoutMainFile(): void
    {
        $service = $this->newService(new RecordingTransport($this->imageResponse(self::JPEG_BYTES)));

        $plan = $service->files_to_optimize([], '/uploads', 'image/jpeg', false, true);

        $this->assertSame([], $plan);
    }

    public function testNonImageMimeNotSupported(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport);

        $this->assertFalse($service->is_supported('video/mp4'));
        $this->assertTrue($service->is_supported('image/jpeg'));
    }

    public function testExceedsLimit(): void
    {
        $transport = new RecordingTransport($this->imageResponse(self::JPEG_BYTES));
        $service = $this->newService($transport);

        $this->assertFalse($service->exceeds_limit(1024));
        $this->assertTrue($service->exceeds_limit(33 * 1024 * 1024));
    }
}
