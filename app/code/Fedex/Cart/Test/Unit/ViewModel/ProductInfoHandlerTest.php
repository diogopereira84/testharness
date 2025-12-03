<?php
/**
 * @category Fedex
 * @package Fedex_Cart
 * @copyright (c) 2022.
 */

namespace Fedex\Cart\Test\Unit\ViewModel;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Quote\Model\Quote\Item\Option;
use Fedex\Cart\ViewModel\ProductInfoHandler;

/**
 * Prepare test objects.
 */
class ProductInfoHandlerTest extends TestCase
{
    protected $abstractItemMock;
    protected $orderItemMock;
    protected $itemOption;
    protected $productInfoHandlerMock;
    protected const GET_OPTION_BY_CODE = 'getOptionByCode';
    protected const GET_PRODUCT_OPTIONS = 'getProductOptions';
    protected const GET_VALUE = 'getValue';
    protected const INFO_BUY_REQUEST = 'info_buyRequest';

    protected function setUp(): void
    {
        $this->abstractItemMock = $this->getMockBuilder(AbstractItem::class)
            ->setMethods([self::GET_OPTION_BY_CODE])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderItemMock = $this->getMockBuilder(OrderItem::class)
            ->setMethods([self::GET_PRODUCT_OPTIONS])
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemOption = $this->getMockBuilder(Option::class)
            ->setMethods([self::GET_VALUE])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->productInfoHandlerMock = $objectManagerHelper->getObject(ProductInfoHandler::class, []);
    }

    /**
     * Test testGetItemExternalProdWithFalse
     *
     * @return bool
     */
    public function testGetItemExternalProdWithFalse()
    {
        $item = '';
        $this->assertEquals(false, $this->productInfoHandlerMock->getItemExternalProd($item));
    }

    /**
     * Test testGetItemExternalProd
     *
     * @return bool
     */
    public function testGetItemExternalProd()
    {
        $this->testGetInfoBuyRequestElse();
        $this->assertEquals([], $this->productInfoHandlerMock->getItemExternalProd($this->abstractItemMock));
    }

    /**
     * Test testGetItemExternalProdData
     *
     * @return bool
     */
    public function testGetItemExternalProdData()
    {
        $this->testGetInfoBuyRequest();
        $this->assertNotNull($this->productInfoHandlerMock->getItemExternalProd($this->abstractItemMock));
    }

    /**
     * Test testGetFxoMenuId
     *
     * @return bool
     */
    public function testGetFxoMenuId()
    {
        $this->testGetInfoBuyRequest();
        $this->assertNotNull($this->productInfoHandlerMock->getFxoMenuId($this->abstractItemMock));
    }

    /**
     * Test testGetProductConfig
     *
     * @return bool
     */
    public function testGetProductConfig()
    {
        $this->testGetInfoBuyRequest();
        $this->assertNotNull($this->productInfoHandlerMock->getProductConfig($this->abstractItemMock));
    }

    /**
     * Test testGetProductRateTotal
     *
     * @return bool
     */
    public function testGetProductRateTotal()
    {
        $this->testGetInfoBuyRequest();
        $this->assertNotNull($this->productInfoHandlerMock->getProductRateTotal($this->abstractItemMock));
    }

    /**
     * Test testGetQuantityChoices
     *
     * @return bool
     */
    public function testGetQuantityChoices()
    {
        $this->testGetInfoBuyRequest();
        $this->assertNotNull($this->productInfoHandlerMock->getQuantityChoices($this->abstractItemMock));
    }

    /**
     * Test testGetFileManagementState
     *
     * @return bool
     */
    public function testGetFileManagementState()
    {
        $this->testGetInfoBuyRequest();
        $this->assertNotNull($this->productInfoHandlerMock->getFileManagementState($this->abstractItemMock));
    }

    /**
     * Test testGetDesign
     *
     * @return bool
     */
    public function testGetDesign()
    {
        $this->testGetItemExternalProdData();
        $this->assertNotNull($this->productInfoHandlerMock->getDesign($this->abstractItemMock));
    }

    /**
     * Test testGetDesignWithElse
     *
     * @return bool
     */
    public function testGetDesignWithElse()
    {
        $abstractItemData = '{
            "productConfig": {
                "designProduct": {
                   "designId": 1
                }
             }
        }';

        $this->abstractItemMock->expects($this->any())->method(self::GET_OPTION_BY_CODE)
        ->with(self::INFO_BUY_REQUEST)
        ->willReturn($this->itemOption);
        $this->itemOption->expects($this->any())->method(self::GET_VALUE)->willReturn($abstractItemData);

        $this->assertNotNull($this->productInfoHandlerMock->getDesign($this->abstractItemMock));
    }

    /**
     * Test getInfoBuyRequest
     *
     * @return bool
     */
    public function testGetInfoBuyRequest()
    {
        $abstractItemData = '{
            "external_prod": [{
                "fxo_product": {
                    "fxoProductInstance": {
                        "productConfig": {
                           "designProduct": {
                              "designId": 1
                           }
                        }
                    }
                }
            }],
            "userProductName":"Flyers",
            "fxoMenuId":"1614105200640-4",
            "productConfig":[],
            "productRateTotal":[],
            "quantityChoices": [],
            "fileManagementState": []
        }';

        $this->abstractItemMock->expects($this->any())->method(self::GET_OPTION_BY_CODE)
        ->with(self::INFO_BUY_REQUEST)
        ->willReturn($this->itemOption);
        $this->itemOption->expects($this->any())->method(self::GET_VALUE)->willReturn($abstractItemData);

        $this->assertNotNull($this->productInfoHandlerMock->getInfoBuyRequest($this->itemOption));
    }

    /**
     * Test getInfoBuyRequestElse
     *
     * @return bool
     */
    public function testGetInfoBuyRequestElse()
    {
        $this->orderItemMock->expects($this->any())->method(self::GET_PRODUCT_OPTIONS)
        ->willReturn([self::INFO_BUY_REQUEST => []]);
        $this->assertEquals([], $this->productInfoHandlerMock->getInfoBuyRequest($this->orderItemMock));
    }

    /**
     * Test getInfoBuyRequestWithNull
     *
     * @return bool
     */
    public function testGetInfoBuyRequestWithNull()
    {
        $this->assertEquals(false, $this->productInfoHandlerMock->getInfoBuyRequest(null));
    }
}
