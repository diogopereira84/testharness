<?php

/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Attri Kumar <attri.kumar.osv@fedex.com>
 * @copyright 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Plugin\Model\Address;

use Fedex\Delivery\Plugin\Model\Address\Rate;
use Magento\Quote\Model\Cart\ShippingMethod;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RateTest extends TestCase
{
    /**
     * @var MockObject|ToggleConfig
     */
    protected $toggleConfigMock;

    /**
     * @var MockObject|Data
     */
    protected $dataHelperMock;

    /**
     * @var MockObject|ShippingMethod
     */
    protected $shippingMethodMock;

    /**
     * @var MockObject|Rate
     */
    protected $rateMock;

    /**
     * @var MockObject|Method
     */
    protected $rate;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods(['isCommercialCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethodMock = $this->getMockBuilder(ShippingMethod::class)
            ->setMethods(['setProductionLocation'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rate = $this->getMockBuilder(Method::class)
            ->setMethods(['getProductionLocation'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateMock = new Rate(
            $this->toggleConfigMock,
            $this->dataHelperMock
        );
    }

    /**
     * @return void
     */
    public function testAfterImportShippingRate()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tech_titans_d_213795')
            ->willReturn(true);
        $this->dataHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->shippingMethodMock->expects($this->any())
            ->method('setProductionLocation')
            ->willReturnSelf();
        $this->assertNotEquals(
            null,
            $this->rateMock->afterImportShippingRate(
                $this->rate,
                $this->shippingMethodMock,
                $this->rate
            )
        );
    }
}
