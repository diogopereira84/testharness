<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\FXOCMConfigurator\Test\Unit\Model;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\FXOCMConfigurator\Model\ConfigProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\Cart;
use Fedex\Cart\ViewModel\ProductInfoHandler;
use Magento\Quote\Api\Data\CartItemInterface;

/**
 * ConfigProviderTest Model
 */
class ConfigProviderTest extends TestCase
{
    protected $itemMock;
    protected $cartMock;
    protected $productInfoHandlerMock;
    /**
     * @var ConfigProvider|MockObject
     */
    private $configProvider;

    /**
     * @var ConfigInterface $configInterface
     */
    protected $config;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->itemMock = $this->getMockBuilder(CartItemInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setQty', 'getExtensionAttributes'])
            ->addMethods([
                'getData',
                'getId',
                'getOptionByCode',
                'addOption',
                'getProductId',
                'saveItemOptions',
                'setInstanceId',
                'save'
            ])
            ->getMockForAbstractClass();

        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMock();

       $this->productInfoHandlerMock = $this->getMockBuilder(ProductInfoHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItemExternalProd'])
            ->getMock();     

        $objectManagerHelper = new ObjectManager($this);
        $this->configProvider = $objectManagerHelper->getObject(
            ConfigProvider::class,
            [
                'cart' => $this->cartMock,
                'productInfoHandler' => $this->productInfoHandlerMock
            ]
        );
    }
    /**
     * Test getConfig
     *
     * @return void
     */
    public function testGetConfig()
    {
        $this->cartMock->expects($this->any())->method('getItems')->willReturn([$this->itemMock]);
        $this->productInfoHandlerMock->expects($this->any())->method('getItemExternalProd')->willReturn('167806458450076658428002803992001865409131');
        $this->assertNotNull($this->configProvider->getConfig());
    }
}
