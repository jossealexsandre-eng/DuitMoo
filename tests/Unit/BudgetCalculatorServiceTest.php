<?php

namespace Tests\Unit;

use App\Services\BudgetCalculatorService;
use PHPUnit\Framework\TestCase;

class BudgetCalculatorServiceTest extends TestCase
{
    private BudgetCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BudgetCalculatorService();
    }

    public function test_standard_5_million_allocation_60_30_10(): void
    {
        $result = $this->service->calculateAllocation(5000000, 60, 30, 10);

        $this->assertEquals(3000000, $result['needs']);
        $this->assertEquals(1500000, $result['wants']);
        $this->assertEquals(500000, $result['emergency']);
        $this->assertEquals(5000000, $result['total']);
        $this->assertEquals(5000000, $result['needs'] + $result['wants'] + $result['emergency']);
    }

    public function test_custom_ratio_50_30_20(): void
    {
        $result = $this->service->calculateAllocation(5000000, 50, 30, 20);

        $this->assertEquals(2500000, $result['needs']);
        $this->assertEquals(1500000, $result['wants']);
        $this->assertEquals(1000000, $result['emergency']);
        $this->assertEquals(5000000, $result['total']);
        $this->assertEquals(5000000, $result['needs'] + $result['wants'] + $result['emergency']);
    }

    public function test_invalid_ratio_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->calculateAllocation(5000000, 60, 30, 20); // total 110 != 100
    }

    public function test_odd_numbers_and_one_rupiah_preserves_total(): void
    {
        // Test 1 Rupiah
        $res1 = $this->service->calculateAllocation(1, 60, 30, 10);
        $this->assertEquals(1, $res1['needs'] + $res1['wants'] + $res1['emergency']);

        // Test 101 Rupiah
        $res101 = $this->service->calculateAllocation(101, 60, 30, 10);
        $this->assertEquals(101, $res101['needs'] + $res101['wants'] + $res101['emergency']);

        // Test random non-divisible amount: 3,333,333
        $resOdd = $this->service->calculateAllocation(3333333, 60, 30, 10);
        $this->assertEquals(3333333, $resOdd['needs'] + $resOdd['wants'] + $resOdd['emergency']);
    }

    public function test_zero_amount(): void
    {
        $result = $this->service->calculateAllocation(0, 60, 30, 10);
        $this->assertEquals(0, $result['needs']);
        $this->assertEquals(0, $result['wants']);
        $this->assertEquals(0, $result['emergency']);
        $this->assertEquals(0, $result['total']);
    }
}
