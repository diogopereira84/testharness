<?php

/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Attri Kumar <attri.kumar.osv@fedex.com>
 * @copyright 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Plugin\Model;

use Fedex\Delivery\Plugin\Model\ProductionLocation;
use Magento\Quote\Api\Data\ShippingMethodInterfaceFactory;
use Magento\Quote\Model\Cart\ShippingMethod;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote\Address\Rate;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Delivery\Helper\Data;
use Magento\Quote\Api\Data\ShippingMethodExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductionLocationTest extends TestCase
{
    /**
     * @var MockObject|ShippingMethodInterfaceFactory
     */
    protected $shippingMethodDataFactoryMock;

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
    protected $rateModelMock;

    /**
     * @var MockObject|ShippingMethodExtensionInterface
     */
    protected $shippingMethodExtensionInterface;

    /**
     * @var MockObject|productionLocationMock
     */
    protected $productionLocationMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->shippingMethodDataFactoryMock = $this->getMockBuilder(ShippingMethodInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->dataHelperMock = $this->getMockBuilder(Data::class)
            ->setMethods(['isCommercialCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingMethodMock = $this->getMockBuilder(ShippingMethod::class)
            ->addMethods(['create'])
            ->setMethods(['setProductionLocation', 'getExtensionAttributes', 'setExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rateModelMock = $this->getMockBuilder(Rate::class)
            ->addMethods(['getProductionLocation'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productionLocationMock = new ProductionLocation(
            $this->shippingMethodDataFactoryMock,
            $this->toggleConfigMock,
            $this->dataHelperMock
        );
    }

    /**
     * @return void
     */
    public function testAfterModelToDataObject()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('tech_titans_d_213795')
            ->willReturn(true);
        $this->dataHelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(false);

        $this->assertNotEquals(
            Null,
            $this->productionLocationMock->afterModelToDataObject($this->rateModelMock, $this->shippingMethodMock, $this->rateModelMock)
        );
    }
}
