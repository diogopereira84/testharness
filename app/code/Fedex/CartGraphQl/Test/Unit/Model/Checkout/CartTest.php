<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Checkout;

use Fedex\CartGraphQl\Model\Checkout\Cart;
use Magento\GraphQl\Model\Query\Context;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    protected $storeMock;
    protected $getCartForUserMock;
    protected $context;
    protected $contextExtensionMock;
    protected $quoteMock;
    protected $addressMock;
    protected $cart;
    protected function setUp(): void
    {
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getCartForUserMock = $this->createMock(GetCartForUser::class);
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextExtensionMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->contextExtensionMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->context->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->contextExtensionMock);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['getCustomerTelephone'])
            ->onlyMethods(['isSaveAllowed', 'getItemsCount'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->cart = $objectManager->getObject(
            Cart::class,
            [
                'getCartForUser' => $this->getCartForUserMock
            ]
        );
    }

    public function testGetCart()
    {
        $this->getCartForUserMock->expects($this->once())
            ->method('execute')
            ->willReturn($this->quoteMock);

        $cart = $this->cart->getCart('1', $this->context);
        $this->assertInstanceOf(Quote::class, $cart);
    }

    public function testSetContactInfo()
    {
        $firstname = 'John';
        $shippingContact = ['firstname' => $firstname];
        $this->addressMock->expects($this->any())
            ->method('getFirstname')
            ->willReturn($firstname);
        $this->cart->setContactInfo($this->addressMock, $shippingContact);
        $this->assertEquals($firstname, $this->addressMock->getFirstname());
    }

    public function testSetCustomerCartData()
    {
        $customerTelephone = '7894565454';
        $shippingContact = ['telephone' => $customerTelephone];
        $this->quoteMock->expects($this->any())
            ->method('getCustomerTelephone')
            ->willReturn('7894565454');
        $this->cart->setCustomerCartData($this->quoteMock, $shippingContact, []);
        $this->assertEquals($customerTelephone, $this->quoteMock->getCustomerTelephone());
    }

    public function testCheckIfQuoteIsEmpty()
    {
        $this->quoteMock->expects($this->any())
            ->method('getItemsCount')
            ->willReturn(0);
        $this->assertEquals(1, $this->cart->checkIfQuoteIsEmpty($this->quoteMock));
    }
}
