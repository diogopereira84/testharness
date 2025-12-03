<?php

/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 * @copyright 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Plugin\Model;

use Fedex\Delivery\Model\GetCheapestFastestShippingMethod;
use Fedex\Delivery\Plugin\Model\CheapestFastestShippingPlugin;
use Magento\Quote\Model\ShippingMethodManagement;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheapestFastestShippingPluginTest extends TestCase
{
    /**
     * Xpath for shipping methods display toggle
     */
    private const XPATH_DISPLAY_SHIPPING_METHODS_TOGGLE
        = 'environment_toggle_configuration/environment_toggle/tiger_e_427646_shipping_methods_display';

    /** @var GetCheapestFastestShippingMethod|MockObject */
    private $getCheapestFastestShippingMethod;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CheapestFastestShippingPlugin|MockObject */
    private $shippingMethodAttributes;

    /** @var ShippingMethodManagement|MockObject */
    private $shippingMethodManagement;

    /** @var ScopeConfigInterface|MockObject */
    private $scopeConfig;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->getCheapestFastestShippingMethod = $this->getMockBuilder(GetCheapestFastestShippingMethod::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $this->shippingMethodManagement = $this->createMock(ShippingMethodManagement::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->shippingMethodAttributes = new CheapestFastestShippingPlugin(
            $this->getCheapestFastestShippingMethod,
            $this->scopeConfig,
            $this->logger
        );
    }

    /**
     * @return void
     */
    public function testAfterEstimateByExtendedAddressSuccess()
    {
        $result = $this->getSampleShippingMethods();
        $expected = $this->getExpectedShippingMethods();

        $this->scopeConfig
            ->method('isSetFlag')
            ->with(self::XPATH_DISPLAY_SHIPPING_METHODS_TOGGLE)
            ->willReturn(true);

        $this->getCheapestFastestShippingMethod->expects($this->once())
            ->method('execute')
            ->with($result)
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            $this->shippingMethodAttributes->afterEstimateByExtendedAddress($this->shippingMethodManagement, $result)
        );
    }

    /**
     * Testing with toggle disabled the result should to be the same
     * @return void
     */
    public function testAfterEstimateByExtendedAddressSuccessWithToggleDisabled()
    {
        $result = $this->getSampleShippingMethods();

        $this->scopeConfig
            ->method('isSetFlag')
            ->with(self::XPATH_DISPLAY_SHIPPING_METHODS_TOGGLE)
            ->willReturn(false);

        $this->assertEquals(
            $result,
            $this->shippingMethodAttributes->afterEstimateByExtendedAddress($this->shippingMethodManagement, $result)
        );
    }

    /**
     * @return array[]
     */
    private function getSampleShippingMethods(): array
    {
        return [
            [
                "carrier_code" => "marketplace_2643",
                "method_code" => "GROUND_US_HOME_DELIVERY",
                "carrier_title" => "Printful",
                "method_title" => "FedEx Ground",
                "amount" => 14,
                "base_amount" => 14,
                "available" => true,
                "price_incl_tax" => 14,
                "price_excl_tax" => 14,
                "offer_id" => "2643",
                "title" => "Printful",
                "selected" => "marketplace_2643_STANDARD_OVERNIGHT",
                "selected_code" => "STANDARD_OVERNIGHT",
                "item_id" => "170614",
                "shipping_type_label" => "FedEx Ground",
                "deliveryDate" => "Friday, September 20, 3:59am",
                "deliveryDateText" => "Friday, September 20, 3:59am",
                "marketplace" => true,
                "seller_id" => "2002",
                "seller_name" => "Marketplace Seller - Custom Apparel"
            ],
            [
                "carrier_code" => "marketplace_2643",
                "method_code" => "STANDARD_OVERNIGHT",
                "carrier_title" => "Printful",
                "method_title" => "FedEx Standard Overnight",
                "amount" => 25.34,
                "base_amount" => 25.34,
                "available" => true,
                "price_incl_tax" => 25.34,
                "price_excl_tax" => 25.34,
                "offer_id" => "2643",
                "title" => "Printful",
                "selected" => "marketplace_2643_STANDARD_OVERNIGHT",
                "selected_code" => "STANDARD_OVERNIGHT",
                "item_id" => "170614",
                "shipping_type_label" => "FedEx Standard Overnight",
                "deliveryDate" => "Thursday, September 19, 3:59am",
                "deliveryDateText" => "Thursday, September 19, 3:59am",
                "marketplace" => true,
                "seller_id" => "2002",
                "seller_name" => "Marketplace Seller - Custom Apparel"
            ],
            [
                "carrier_code" => "marketplace_2643",
                "method_code" => "PRIORITY_OVERNIGHT",
                "carrier_title" => "Printful",
                "method_title" => "FedEx Priority Overnight",
                "amount" => 28.32,
                "base_amount" => 28.32,
                "available" => true,
                "price_incl_tax" => 28.32,
                "price_excl_tax" => 28.32,
                "offer_id" => "2643",
                "title" => "Printful",
                "selected" => "marketplace_2643_STANDARD_OVERNIGHT",
                "selected_code" => "STANDARD_OVERNIGHT",
                "item_id" => "170614",
                "shipping_type_label" => "FedEx Priority Overnight",
                "deliveryDate" => "Wednesday, September 18, 3:59am",
                "deliveryDateText" => "Wednesday, September 18, 3:59am",
                "marketplace" => true,
                "seller_id" => "2002",
                "seller_name" => "Marketplace Seller - Custom Apparel"
            ]
        ];
    }

    /**
     * @return array|array[]
     */
    private function getExpectedShippingMethods(): array
    {
        $result = $this->getSampleShippingMethods();
        $result[0]['extension_attributes'] = ['fastest' => false, 'cheapest' => true];
        $result[1]['extension_attributes'] = ['fastest' => false, 'cheapest' => false];
        $result[2]['extension_attributes'] = ['fastest' => true, 'cheapest' => false];

        return $result;
    }
}
