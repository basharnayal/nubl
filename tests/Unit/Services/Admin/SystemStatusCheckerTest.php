<?php

namespace Tests\Unit\Services\Admin;

use App\Models\SystemSetting;
use App\Services\Admin\Dashboard\SystemStatusChecker;
use App\Support\WeeklyAllowanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemStatusCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected string $maintenancePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->maintenancePath = storage_path('framework/maintenance.php');
        if (file_exists($this->maintenancePath)) {
            unlink($this->maintenancePath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->maintenancePath)) {
            unlink($this->maintenancePath);
        }

        parent::tearDown();
    }

    #[Test]
    public function checker_returns_expected_default_statuses_when_no_overrides_exist(): void
    {
        config(['services.myfatoorah.api_key' => null]);

        $items = collect((new SystemStatusChecker)->all())->keyBy('key');

        $this->assertCount(5, $items);

        $this->assertTrue($items['maintenance']['is_ok']);
        $this->assertSame('ok', $items['maintenance']['severity']);

        $this->assertTrue($items['qr']['is_ok']);
        $this->assertSame('dashboard.status.tooltip.qr_default', $items['qr']['tooltip_key']);
        $this->assertSame((int) config('qr.ttl_minutes', 180), $items['qr']['tooltip_params']['minutes']);

        $this->assertFalse($items['allowance']['is_ok']);
        $this->assertSame('warning', $items['allowance']['severity']);

        $this->assertTrue($items['allocation']['is_ok']);
        $this->assertSame('ok', $items['allocation']['severity']);

        $this->assertFalse($items['gateway']['is_ok']);
        $this->assertSame('high', $items['gateway']['severity']);
    }

    #[Test]
    public function checker_reflects_maintenance_and_database_overrides(): void
    {
        config(['services.myfatoorah.api_key' => 'api-key']);

        if (! is_dir(dirname($this->maintenancePath))) {
            mkdir(dirname($this->maintenancePath), 0777, true);
        }
        file_put_contents($this->maintenancePath, '<?php return [];');

        SystemSetting::setValue('qr.ttl_minutes', '30');
        SystemSetting::setValue(WeeklyAllowanceSettings::KEY_ACTIVE, '500');
        SystemSetting::setValue('allocation_engine.paused', '1');

        $items = collect((new SystemStatusChecker)->all())->keyBy('key');

        $this->assertFalse($items['maintenance']['is_ok']);
        $this->assertSame('high', $items['maintenance']['severity']);
        $this->assertSame('dashboard.status.tooltip.maintenance_on', $items['maintenance']['tooltip_key']);

        $this->assertSame('dashboard.status.tooltip.qr_configured', $items['qr']['tooltip_key']);
        $this->assertSame(30, $items['qr']['tooltip_params']['minutes']);

        $this->assertTrue($items['allowance']['is_ok']);
        $this->assertSame('ok', $items['allowance']['severity']);
        $this->assertSame('500', $items['allowance']['tooltip_params']['amount']);

        $this->assertFalse($items['allocation']['is_ok']);
        $this->assertSame('warning', $items['allocation']['severity']);

        $this->assertTrue($items['gateway']['is_ok']);
        $this->assertSame('ok', $items['gateway']['severity']);
    }
}

