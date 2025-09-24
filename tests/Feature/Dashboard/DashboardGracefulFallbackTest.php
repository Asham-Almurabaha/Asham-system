<?php

namespace Tests\Feature\Dashboard;

use App\Services\DashboardDataService;
use App\Services\ProductAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardGracefulFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_build_returns_empty_state_when_ledger_table_missing(): void
    {
        Schema::dropIfExists('ledger_entries');

        $result = app(DashboardDataService::class)->build();

        $this->assertSame(0.0, $result['invTotals']->net);
        $this->assertInstanceOf(Collection::class, $result['invByInvestor']);
        $this->assertTrue($result['invByInvestor']->isEmpty());
        $this->assertSame([], $result['timeSeries']['labels']);
        $this->assertSame(0, $result['entriesCount']);
    }

    public function test_product_availability_returns_empty_state_when_dependencies_missing(): void
    {
        Schema::dropIfExists('ledger_entries');

        $result = app(ProductAvailabilityService::class)->build();

        $this->assertSame([], $result['items']);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('stock', $result['totals']);
        $this->assertSame(0, $result['totals']['stock']['available']);
        $this->assertSame('0', $result['totals']['stock']['formatted']);
        $this->assertSame(0.0, $result['totals']['ledger']['balance']);
    }
}
