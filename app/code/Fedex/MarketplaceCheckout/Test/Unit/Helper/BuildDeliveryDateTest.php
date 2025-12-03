<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Helper\BuildDeliveryDate;
use Magento\Framework\App\Helper\Context;
use \Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class BuildDeliveryDateTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var BuildDeliveryDate
     */
    private $buildDeliveryDate;

    /**
     * @var ToggleConfig
     */
    private $toggleConfig;

    /**
     * Setup test environment before each test.
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buildDeliveryDate = new BuildDeliveryDate($this->contextMock, $this->toggleConfig);
    }

    /**
     * @return void
     */
    public function testCutOffLimitBeforeNoon(): void
    {
        $date = '2023-06-21 11:00:00';
        $expectedDate = '2023-06-21 11:00:00';
        $cutOffLimit = '12 PM';
        $additionalProcessingDays = 1;
        $timezone = 'CST';

        $actualDate = $this->buildDeliveryDate->cutOffLimit($date, $cutOffLimit, $additionalProcessingDays, $timezone);

        $this->assertEquals($expectedDate, $actualDate);
    }

    /**
     * @return void
     */
    public function testCutOffLimitPastNoon(): void
    {
        $date = '2023-06-21 13:00:00';
        $expectedDate = '2023-06-22 13:00:00';
        $cutOffLimit = '12 PM';
        $additionalProcessingDays = 1;
        $timezone = 'CST';

        $actualDate = $this->buildDeliveryDate->cutOffLimit($date, $cutOffLimit, $additionalProcessingDays, $timezone);

        $this->assertEquals($expectedDate, $actualDate);
    }

    /**
     * @return void
     */
    public function testCutOffLimitWhenDateIsHoliday(): void
    {
        $date = '2023-12-25 10:00:00';
        $cutOffLimit = '12 PM';
        $additionalProcessingDays = 1;
        $timezone = 'CST';

        $reflection = new \ReflectionClass($this->buildDeliveryDate);
        $holidaysProp = $reflection->getProperty('holidaysList');
        $holidaysProp->setAccessible(true);
        $holidaysProp->setValue($this->buildDeliveryDate, ['12/25/2023']);

        $result = $this->buildDeliveryDate->cutOffLimit($date, $cutOffLimit, $additionalProcessingDays, $timezone);

        $this->assertEquals(date('Y-m-d G:i:s', strtotime($date)), $result);
    }

    /**
     * @return void
     */
    public function testCutOffLimitWithHolidayRollover(): void
    {
        $date = '2023-06-21 13:00:00';
        $cutOffLimit = '12 PM';
        $additionalProcessingDays = 1;
        $timezone = 'America/Chicago';

        $reflection = new \ReflectionClass($this->buildDeliveryDate);
        $holidaysProp = $reflection->getProperty('holidaysList');
        $holidaysProp->setAccessible(true);
        $holidaysProp->setValue($this->buildDeliveryDate, ['06/22/2023']);

        $expectedDate = '2023-06-23 13:00:00';

        $actualDate = $this->buildDeliveryDate->cutOffLimit($date, $cutOffLimit, $additionalProcessingDays, $timezone);

        $this->assertEquals($expectedDate, $actualDate);
    }

    /**
     * @return void
     */
    public function testIsWeekend(): void
    {
        $weekendDate = '2023-06-24';
        $weekdayDate = '2023-06-21';

        $this->assertTrue($this->buildDeliveryDate->isWeekend($weekendDate));
        $this->assertFalse($this->buildDeliveryDate->isWeekend($weekdayDate));
    }

    /**
     * @return void
     */
    public function testGetAllowedDeliveryDate(): void
    {
        $date = '2023-06-21 12:00:00';
        $businessDays = 3;
        $expectedTimestamp = strtotime('2023-06-28 12:00:00');
        $cutOffLimit = '12 PM';
        $holidays = '06/26/2023,06/27/2023';
        $additionalProcessingDays = 1;
        $timezone = 'CST';

        $timestamp = $this->buildDeliveryDate
            ->getAllowedDeliveryDate(
                $date,
                $businessDays,
                $cutOffLimit,
                $holidays,
                $additionalProcessingDays,
                $timezone
            );

        $this->assertNotNull($timestamp);
    }

    /**
     * @return void
     */
    public function testAddBusinessDays(): void
    {
        $date = '2023-06-21 12:00:00';
        $businessDays = 5;
        $expectedDate = '2023-06-27 12:00:00';
        $actualDate = $this->buildDeliveryDate->addBusinessDays($date, $businessDays, false);

        $this->assertNotNull($actualDate);
    }

    /**
     * @return void
     */
    public function testAddProductionDays(): void
    {
        $date = '2024-03-08 12:00:00';
        $productionDays = '15';
        $expectedDate = '2024-03-28 12:00:00';
        $actualDate = $this->buildDeliveryDate->addProductionDays($date, $productionDays);

        $this->assertEquals($expectedDate, $actualDate);
    }

    /**
     * @return void
     */
    public function testGetNextWeekday(): void
    {
        $date = '2023-06-24 12:00:00';
        $expectedDate = '2023-06-26 12:00:00';
        $actualDate = $this->buildDeliveryDate->getNextWeekday($date);

        $this->assertEquals($expectedDate, $actualDate);
    }
}
