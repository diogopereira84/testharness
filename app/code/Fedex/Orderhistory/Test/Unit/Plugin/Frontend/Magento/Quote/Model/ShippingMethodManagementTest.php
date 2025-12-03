<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Plugin\Frontend\Magento\Quote\Model;

use Fedex\Orderhistory\Helper\Data;
use Fedex\Orderhistory\Plugin\Frontend\Magento\Quote\Model\ShippingMethodManagement as Plugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\ShippingMethodManagement;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\AddressInterface;

class ShippingMethodManagementTest extends \PHPUnit\Framework\TestCase
{

    protected $quoteRepository;
    protected $cartInterface;
    protected $addressInterface;
    protected $helper;
    protected $subject;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $plugin;
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['getActive'])
                        ->getMockForAbstractClass();

        $this->cartInterface = $this->getMockBuilder(CartInterface::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['getShippingAddress'])
                        ->getMockForAbstractClass();

        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
                        ->disableOriginalConstructor()
                        ->setMethods(['getCountryId'])
                        ->getMockForAbstractClass();

        $this->helper = $this->getMockBuilder(Data::class)
                        ->setMethods(['isModuleEnabled'])
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->subject = $this->getMockBuilder(ShippingMethodManagement::class)
                        ->disableOriginalConstructor()
                        ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->plugin = $this->objectManager->getObject(
            Plugin::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'helper' => $this->helper
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function testAroundGetList()
    {

        $cartId = 345;
        $proceed = function () {
            $this->subject->getList(345);
        };
        
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->quoteRepository->expects($this->any())->method('getActive')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getCountryId')->willReturn("");

        $this->assertEquals(
            [],
            $this->plugin->aroundGetList($this->subject, $proceed, $cartId)
        );
    }

    /**
     * @inheritDoc
     */
    public function testAroundGetListWithCountryId()
    {

        $cartId = 345;
        $proceed = function () {
            $this->subject->getList(345);
        };
        
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->quoteRepository->expects($this->any())->method('getActive')->willReturn($this->cartInterface);
        $this->cartInterface->expects($this->any())->method('getShippingAddress')->willReturn($this->addressInterface);
        $this->addressInterface->expects($this->any())->method('getCountryId')->willReturn("US");

        $this->assertEquals(
            null,
            $this->plugin->aroundGetList($this->subject, $proceed, $cartId)
        );
    }
}
