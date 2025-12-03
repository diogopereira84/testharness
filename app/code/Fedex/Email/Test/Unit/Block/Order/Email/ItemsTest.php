<?php

declare(strict_types=1);

namespace Fedex\Email\Test\Unit\Block\Order\Email;

use Fedex\Email\Block\Order\Email\Items;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fedex\Email\Block\Order\Email\Items
 */
class ItemsTest extends TestCase
{
    private Context      $context;
    private PriceHelper  $priceHelper;
    private Emulation    $emulation;
    private ToggleConfig $toggleConfig;

    public function setUp(): void
    {
        $this->context      = $this->createMock(Context::class);
        $this->priceHelper  = $this->createMock(PriceHelper::class);
        $this->emulation    = $this->createMock(Emulation::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
    }

    public function testFormattedCurrencyValue(): void
    {
        $value     = 123.45;
        $formatted = '$123.45';

        $this->priceHelper
            ->expects($this->once())
            ->method('currency')
            ->with($value, true, false)
            ->willReturn($formatted);

        $block = new Items(
            $this->context,
            $this->priceHelper,
            $this->emulation,
            $this->toggleConfig
        );
        $this->assertSame($formatted, $block->formattedCurrencyValue($value));
    }

    public function testGetViewFileUrl(): void
    {
        $fileId   = 'items.phtml';
        $params   = ['param1' => 'value1'];
        $expected = 'https://fedex.com/assets/images/logo.png';

        // request->isSecure() → false
        $request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $request->method('isSecure')->willReturn(false);

        // assetRepo->getUrlWithParams()
        $assetRepo = $this->createMock(\Magento\Framework\View\Asset\Repository::class);
        $assetRepo->expects($this->once())
            ->method('getUrlWithParams')
            ->with($fileId, $params + ['_secure' => false])
            ->willReturn($expected);

        // context → assetRepo + request
        $this->context->method('getAssetRepository')->willReturn($assetRepo);
        $this->context->method('getRequest')->willReturn($request);

        // emulation start/stop
        $this->emulation
            ->expects($this->once())
            ->method('startEnvironmentEmulation')
            ->with(0, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $this->emulation
            ->expects($this->once())
            ->method('stopEnvironmentEmulation');

        $block = new Items(
            $this->context,
            $this->priceHelper,
            $this->emulation,
            $this->toggleConfig
        );
        $this->assertSame($expected, $block->getViewFileUrl($fileId, $params));
    }

    public function testGetShipmentItemsFormatted(): void
    {
        $raw = [
            ['sku' => 'item1'],
            ['sku' => 'item2', 'mirakl_shop_name' => 'shopA'],
            ['sku' => 'item3', 'mirakl_shop_name' => 'shopA'],
            ['sku' => 'item4', 'mirakl_shop_name' => 'shopB'],
            ['sku' => 'item5'],
        ];
        $want = [
            '1p' => [0 => ['sku' => 'item1'], 4 => ['sku' => 'item5']],
            '3p' => [
                'shopA' => [
                    ['sku' => 'item2', 'mirakl_shop_name' => 'shopA'],
                    ['sku' => 'item3', 'mirakl_shop_name' => 'shopA'],
                ],
                'shopB' => [['sku' => 'item4', 'mirakl_shop_name' => 'shopB']],
            ],
        ];

        // partial mock to override getShipmentItems()
        $block = $this->getMockBuilder(Items::class)
            ->setConstructorArgs([
                $this->context,
                $this->priceHelper,
                $this->emulation,
                $this->toggleConfig
            ])
            ->addMethods(['getShipmentItems'])
            ->getMock();
        $block->method('getShipmentItems')->willReturn($raw);

        $this->assertSame($want, $block->getShipmentItemsFormatted());
    }

    /**
     * @dataProvider shippingNameProvider
     */
    public function testFormatShippingMethodName(string $input, string $expected): void
    {
        $block = new Items(
            $this->context,
            $this->priceHelper,
            $this->emulation,
            $this->toggleConfig
        );
        $this->assertSame($expected, $block->formatShippingMethodName($input));
    }

    public function shippingNameProvider(): array
    {
        return [
            'overnight'    => ['Overnight',    'FedEx Overnight'],
            'already-fedex' => ['FedEx Ground', 'FedEx Ground'],
        ];
    }

    /**
     * @dataProvider expectedDeliveryFlagProvider
     */
    public function testIsExpectedDeliveryDateEnabled(int $toggleValue, bool $expected): void
    {
        $this->toggleConfig
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('sgc_enable_expected_delivery_date')
            ->willReturn($toggleValue);

        $block = new Items(
            $this->context,
            $this->priceHelper,
            $this->emulation,
            $this->toggleConfig
        );
        $this->assertSame($expected, $block->isExpectedDeliveryDateEnabled());
    }

    public function expectedDeliveryFlagProvider(): array
    {
        return [
            'disabled' => [0, false],
            'enabled'  => [1, true],
        ];
    }
}
