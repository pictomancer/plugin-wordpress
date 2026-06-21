<?php

declare(strict_types=1);

namespace Pictomancer\WP\Tests;

use PHPUnit\Framework\TestCase;
use Pictomancer_Stats;

final class StatsTest extends TestCase
{
    public function testMergeStartsFromEmpty(): void
    {
        $merged = Pictomancer_Stats::merge([], 1000, 400);

        $this->assertSame(
            ['files' => 1, 'original_bytes' => 1000, 'optimized_bytes' => 400],
            $merged,
        );
    }

    public function testMergeAccumulatesOntoExistingTotals(): void
    {
        $current = ['files' => 2, 'original_bytes' => 3000, 'optimized_bytes' => 1200];

        $merged = Pictomancer_Stats::merge($current, 1000, 400);

        $this->assertSame(
            ['files' => 3, 'original_bytes' => 4000, 'optimized_bytes' => 1600],
            $merged,
        );
    }

    public function testSummarizeComputesSavedAndReduction(): void
    {
        $summary = Pictomancer_Stats::summarize(
            ['files' => 4, 'original_bytes' => 1000, 'optimized_bytes' => 250],
        );

        $this->assertSame(750, $summary['bytes_saved']);
        $this->assertSame(75.0, $summary['reduction_pct']);
        $this->assertSame(4, $summary['files']);
    }

    public function testSummarizeEmptyHasZeroReduction(): void
    {
        $summary = Pictomancer_Stats::summarize([]);

        $this->assertSame(0, $summary['bytes_saved']);
        $this->assertSame(0.0, $summary['reduction_pct']);
        $this->assertSame(0, $summary['files']);
    }

    public function testSummarizeNeverReportsNegativeSavings(): void
    {
        $summary = Pictomancer_Stats::summarize(
            ['files' => 1, 'original_bytes' => 400, 'optimized_bytes' => 500],
        );

        $this->assertSame(0, $summary['bytes_saved']);
        $this->assertSame(0.0, $summary['reduction_pct']);
    }
}
