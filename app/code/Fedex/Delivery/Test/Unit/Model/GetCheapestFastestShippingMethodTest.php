<?php

/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 * @copyright 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Delivery\Test\Unit\Model;

use Fedex\Delivery\Model\GetCheapestFastestShippingMethod;
use Fedex\Delivery\Model\Shipping\CheapestFastestSelector;
use Fedex\Delivery\Model\Shipping\ShippingMethodFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class GetCheapestFastestShippingMethodTest extends TestCase
{
    private GetCheapestFastestShippingMethod $model;
    private $loggerMock;
    private $extensionFactoryMock;
    private $selectorMock;
    private $shippingMethodFactoryMock;
    private $toggleConfigMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->extensionFactoryMock = $this->createMock(ShippingMethodExtensionFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->selectorMock = $this->createMock(CheapestFastestSelector::class);
        $this->shippingMethodFactoryMock = $this->createMock(ShippingMethodFactory::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $this->model = new GetCheapestFastestShippingMethod(
            $this->extensionFactoryMock,
            $this->loggerMock,
            $this->selectorMock,
            $this->shippingMethodFactoryMock,
            $this->toggleConfigMock
        );
    }

    public function testExecuteWithToggleEnabled(): void
    {
        $shippingMethods = [['method' => 'test']];
        $normalizedMethods = ['normalized'];
        $updatedMethods = ['updated'];

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->shippingMethodFactoryMock->method('createFromArray')->with($shippingMethods)->willReturn($normalizedMethods);
        $this->selectorMock->method('applyCheapestAndFastest')->with($normalizedMethods)->willReturn($updatedMethods);
        $this->shippingMethodFactoryMock->method('convertToArray')->with($shippingMethods, $updatedMethods)->willReturn($updatedMethods);

        $result = $this->model->execute($shippingMethods);

        $this->assertSame($updatedMethods, $result);
    }

    public function testExecuteWithToggleDisabled(): void
    {
        $shippingMethods = [['method' => 'test']];

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);

        $this->model->execute($shippingMethods);

        $this->assertNotEmpty($this->model);
    }

    public function testExecuteThrowsException(): void
    {
        $this->expectException(LocalizedException::class);

        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(true);
        $this->shippingMethodFactoryMock->method('createFromArray')->willThrowException(new LocalizedException(__('Error')));

        $this->model->execute([]);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testExecuteWithValidArray()
    {
        $sampleShippingMethods = $this->getSampleShippingMethods();
        $expected = $this->getExpectedShippingMethods();

        $this->assertEquals(
            $expected,
            $this->model->execute($sampleShippingMethods)
        );
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testExecuteWithInvalidDateTime()
    {
        $this->toggleConfigMock->method('getToggleConfigValue')->willReturn(false);
        $sampleShippingMethods = $this->getSampleShippingMethods();
        $sampleShippingMethods[0]['deliveryDate'] = 'invalid-date';
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('DeliveryDate provided is not valid. (Key Code: 2002)');

        $this->model->execute($sampleShippingMethods);
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
