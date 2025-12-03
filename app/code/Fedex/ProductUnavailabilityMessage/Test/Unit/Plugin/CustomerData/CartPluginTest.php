<?php
declare(strict_types=1);

namespace Fedex\ProductUnavailabilityMessage\Test\Unit\Plugin\CustomerData;

use PHPUnit\Framework\TestCase;
use Magento\Checkout\CustomerData\Cart;
use Fedex\ProductUnavailabilityMessage\ViewModel\CheckProductAvailability;
use Fedex\ProductUnavailabilityMessage\Plugin\CustomerData\CartPlugin;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Psr\Log\LoggerInterface;

class CartPluginTest  extends TestCase
{
    private $cartPlugin;
    private $checkProductAvailabilityMock;
    private $cartMock;

    /**
     * @var MarketPlaceHelper
     */
    private $marketPlaceHelperMock;

    /**
     * @var ConfigProvider
     */
    private $configProviderMock;

    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    protected function setUp(): void
    {
        $this->checkProductAvailabilityMock = $this->createMock(CheckProductAvailability::class);
        $this->cartMock = $this->createMock(Cart::class);
        $this->marketPlaceHelperMock = $this->createMock(MarketPlaceHelper::class);

        $this->configProviderMock = $this->createMock(ConfigProvider::class);

        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->cartPlugin = new CartPlugin($this->checkProductAvailabilityMock,
        $this->marketPlaceHelperMock,
        $this->configProviderMock,
        $this->loggerMock);
    }

    /**
     * @return void
     */
    public function testAfterGetSectionData_WhenToggleEnabled_ShouldReturnModifiedResultArray()
    {
        $result = [
            'unavailable_cart_msg' => null,
            'isE441563ToggleEnabled' => true
        ];
        $modifiedResult = $this->cartPlugin->afterGetSectionData($this->cartMock, $result);
        $this->assertEquals(null, $modifiedResult['unavailable_cart_msg']);
        $this->assertTrue($modifiedResult['isE441563ToggleEnabled']);
    }

    /**
     * @return void
     */
    public function testAfterGetSectionDataReturnUnmodifiedResult()
    {
        $result = [
            'unavailable_cart_msg' => null,
            'isE441563ToggleEnabled' => false
        ];
        $this->checkProductAvailabilityMock->expects($this->once())
            ->method('isE441563ToggleEnabled')
            ->willReturn(false);
        $modifiedResult = $this->cartPlugin->afterGetSectionData($this->cartMock, $result);
        $this->assertEquals(null, $modifiedResult['unavailable_cart_msg']);
        $this->assertFalse($modifiedResult['isE441563ToggleEnabled']);
    }

     public function testAfterGetSectionDataLegacyDocNumericRef()
    {
        $result = [
            'items' => [
                [
                    'item_id' => 101,
                    'productContentAssociation' => [
                        'contentAssociations' => [
                            ['contentReference' => '12345']
                        ]
                    ]
                ]
            ],
            'checkLegacyDocApiOnCartToggle' => true
        ];

        $this->marketPlaceHelperMock->expects($this->once())
            ->method('checkLegacyDocApiOnCartToggle')
            ->willReturn(true);

        $modifiedResult = $this->cartPlugin->afterGetSectionData($this->cartMock, $result);

        $this->assertArrayHasKey('legacyDocumentStatus', $modifiedResult);
        $this->assertTrue($modifiedResult['legacyDocumentStatus'][101]);
    }

    public function testAfterGetSectionDataLegacyDocToggleEnabled()
    {
        $result = [
            'items' => [
                [
                    'item_id' => 102,
                    'productContentAssociation' => [
                        'contentAssociations' => [
                            ['contentReference' => 'not_a_number']
                        ]
                    ]
                ]
            ],
            'checkLegacyDocApiOnCartToggle' => true
        ];

        $this->marketPlaceHelperMock->expects($this->once())
            ->method('checkLegacyDocApiOnCartToggle')
            ->willReturn(true);

        $modifiedResult = $this->cartPlugin->afterGetSectionData($this->cartMock, $result);

        $this->assertArrayHasKey('legacyDocumentStatus', $modifiedResult);
        $this->assertFalse($modifiedResult['legacyDocumentStatus'][102]);
    }

    public function testAfterGetSectionDataLegacyDocToggleDisabled()
    {
        $result = [
            'items' => [
                [
                    'item_id' => 103,
                    'productContentAssociation' => [
                        'contentAssociations' => [
                            ['contentReference' => '12345']
                        ]
                    ]
                ]
            ],
            'checkLegacyDocApiOnCartToggle' => false
        ];

        $this->marketPlaceHelperMock->expects($this->once())
            ->method('checkLegacyDocApiOnCartToggle')
            ->willReturn(false);

        $modifiedResult = $this->cartPlugin->afterGetSectionData($this->cartMock, $result);

        $this->assertArrayNotHasKey('legacyDocumentStatus', $modifiedResult);
    }

}
