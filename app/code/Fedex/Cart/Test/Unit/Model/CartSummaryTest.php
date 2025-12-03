<?php

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model;

use Fedex\Cart\Model\CartSummary;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Fedex\B2b\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Psr\Log\LoggerInterface;

class CartSummaryTest extends \PHPUnit\Framework\TestCase
{
    protected $totalsInformationInterface;
    protected $quoteModel;
    protected $shippingAddressMock;
    protected $addressInterface;
    protected $quoteAddressInterface;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    /**
     * Cart total repository.
     *
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalRepository;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    protected const GET_SUBTOTAL = 'getSubTotal';
    protected const SET_SUBTOTAL = 'setSubTotal';
    protected const GET_GRANDTOTAL = 'getGrandTotal';
    protected const SET_GRANDTOTAL = 'setGrandTotal';
    protected const GET_SHIPPING_CARRIER_CODE = 'getShippingCarrierCode';
    protected const GET_SHIPPING_ADDRESS = 'getShippingAddress';
    protected const SET_SHIPPING_ADDRESS = 'setShippingAddress';
    protected const GET_ADDRESS = 'getAddress';
    protected const GET_SHIPPING_METHOD_CODE = 'getShippingMethodCode';
    protected const GET_ITEMS_COUNT = 'getItemsCount';
    protected const GET_IS_VIRTUAL = 'getIsVirtual';
    protected const SET_BILLING_ADDRESS = 'setBillingAddress';
    protected const COLLECT_TOTALS = 'collectTotals';
    protected const SET_SHIPPING_METHOD = 'setShippingMethod';
    protected const SET_COLLECT_SHIPPING_RATES = 'setCollectShippingRates';
    protected const TX_FLAT = 'TX_Flat';

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->cartRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->setMethods(['get'])
            ->getMockForAbstractClass();
        $this->cartTotalRepository = $this->getMockBuilder(CartTotalRepositoryInterface::class)
            ->setMethods(['get', self::SET_SUBTOTAL, self::SET_GRANDTOTAL])
            ->getMockForAbstractClass();
        $this->totalsInformationInterface = $this->getMockBuilder(TotalsInformationInterface::class)
            ->setMethods([self::GET_ADDRESS, self::GET_SHIPPING_CARRIER_CODE, self::GET_SHIPPING_METHOD_CODE])
            ->getMockForAbstractClass();
        $this->quoteModel = $this->getMockBuilder(Quote::class)
            ->setMethods([
                self::GET_SUBTOTAL,
                self::GET_GRANDTOTAL,
                self::GET_ITEMS_COUNT,
                self::GET_IS_VIRTUAL,
                self::SET_BILLING_ADDRESS,
                self::SET_SHIPPING_ADDRESS,
                self::GET_SHIPPING_ADDRESS,
                self::COLLECT_TOTALS
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteAddressInterface = $this
        ->getMockBuilder(QuoteAddressInterface::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->shippingAddressMock = $this
            ->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods([
                self::SET_SHIPPING_METHOD,
                self::SET_COLLECT_SHIPPING_RATES
            ])
            ->getMock();
        $this->addressInterface = $this->getMockBuilder(AddressInterface::class)
            ->setMethods([
                self::SET_SHIPPING_METHOD,
                self::SET_COLLECT_SHIPPING_RATES
            ])
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(
            CartSummary::class,
            [
                'cartRepository' => $this->cartRepository,
                'cartTotalRepository' => $this->cartTotalRepository,
                'shippingAddressMock' => $this->shippingAddressMock,
                'addressInterface' => $this->addressInterface,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test Method for calculate.
     */
    public function testcalculate()
    {
        $cartId = 2;
        $this->cartRepository->expects($this->any())->method('get')->willReturn($this->quoteModel);
        $this->quoteModel->expects($this->any())->method(self::GET_SUBTOTAL)->willReturn(500);
        $this->quoteModel->expects($this->any())->method(self::GET_GRANDTOTAL)->willReturn(300);
        $this->quoteModel->expects($this->any())->method(self::GET_ITEMS_COUNT)->willReturn(1);
        $this->quoteModel->expects($this->any())->method(self::GET_IS_VIRTUAL)->willReturn(false);
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_ADDRESS)
        ->willReturn($this->quoteAddressInterface);
        $this->quoteModel->expects($this->any())->method(self::SET_BILLING_ADDRESS)->willReturnSelf();
        $this->quoteModel->expects($this->any())->method(self::SET_SHIPPING_ADDRESS)->willReturnSelf();
        $this->quoteModel->expects($this->any())->method(self::GET_SHIPPING_ADDRESS)
        ->willReturn($this->addressInterface);
        $this->quoteModel->expects($this->any())->method(self::COLLECT_TOTALS)->willReturnSelf();
        $this->addressInterface->expects($this->any())->method(self::SET_COLLECT_SHIPPING_RATES)->willReturnSelf();
        $this->addressInterface->expects($this->any())->method(self::SET_SHIPPING_METHOD)->willReturn(self::TX_FLAT);
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_SHIPPING_CARRIER_CODE)
        ->willReturn('TX');
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_SHIPPING_METHOD_CODE)
        ->willReturn('Flat');
        $this->cartTotalRepository->expects($this->any())->method('get')->with($cartId)->willReturnSelf();
        $this->cartTotalRepository->expects($this->any())->method(self::SET_SUBTOTAL)->willReturn(true);
        $this->cartTotalRepository->expects($this->any())->method(self::SET_GRANDTOTAL)->willReturn(true);

        $this->model->calculate($cartId, $this->totalsInformationInterface);
    }

    /**
     * Test Method for calculate with Else condition.
     */
    public function testcalculatewithElse()
    {
        $cartId = 2;
        $this->cartRepository->expects($this->any())->method('get')->willReturn($this->quoteModel);
        $this->quoteModel->expects($this->any())->method(self::GET_SUBTOTAL)->willReturn(500);
        $this->quoteModel->expects($this->any())->method(self::GET_GRANDTOTAL)->willReturn(300);
        $this->quoteModel->expects($this->any())->method(self::GET_ITEMS_COUNT)->willReturn(1);
        $this->quoteModel->expects($this->any())->method(self::GET_IS_VIRTUAL)->willReturn(true);
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_ADDRESS)
        ->willReturn($this->quoteAddressInterface);
        $this->quoteModel->expects($this->any())->method(self::SET_BILLING_ADDRESS)->willReturnSelf();
        $this->quoteModel->expects($this->any())->method(self::SET_SHIPPING_ADDRESS)->willReturnSelf(true);
        $this->quoteModel->expects($this->any())->method(self::GET_SHIPPING_ADDRESS)
        ->willReturn($this->addressInterface);
        $this->quoteModel->expects($this->any())->method(self::COLLECT_TOTALS)->willReturnSelf();
        $this->addressInterface->expects($this->any())->method(self::SET_COLLECT_SHIPPING_RATES)->willReturnSelf();
        $this->addressInterface->expects($this->any())->method(self::SET_SHIPPING_METHOD)->willReturn(self::TX_FLAT);
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_SHIPPING_CARRIER_CODE)
        ->willReturn('TX');
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_SHIPPING_METHOD_CODE)
        ->willReturn('Flat');
        $this->cartTotalRepository->expects($this->any())->method('get')->with($cartId)->willReturnSelf();
        $this->cartTotalRepository->expects($this->any())->method(self::SET_SUBTOTAL)->willReturn(true);
        $this->cartTotalRepository->expects($this->any())->method(self::SET_GRANDTOTAL)->willReturn(true);

        $this->model->calculate($cartId, $this->totalsInformationInterface);
    }
    
    /**
     * Test Method for calculate with Exception.
     */
    public function testcalculatewithException()
    {
        $cartId = 2;
        $this->cartRepository->expects($this->any())->method('get')->willReturn($this->quoteModel);
        $this->quoteModel->expects($this->any())->method(self::GET_SUBTOTAL)->willReturn(500);
        $this->quoteModel->expects($this->any())->method(self::GET_GRANDTOTAL)->willReturn(300);
        $this->quoteModel->expects($this->any())->method(self::GET_ITEMS_COUNT)->willReturn(0);
        $this->expectException("Exception");
        $this->quoteModel->expects($this->any())->method(self::GET_IS_VIRTUAL)->willReturn(true);
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_ADDRESS)
        ->willReturn($this->shippingAddressMock);
        $this->quoteModel->expects($this->any())->method(self::SET_BILLING_ADDRESS)->willReturnSelf();
        $this->quoteModel->expects($this->any())->method(self::SET_SHIPPING_ADDRESS)->willReturnSelf(true);
        $this->quoteModel->expects($this->any())->method(self::GET_SHIPPING_ADDRESS)
        ->willReturn($this->addressInterface);
        $this->quoteModel->expects($this->any())->method(self::COLLECT_TOTALS)->willReturnSelf();
        $this->addressInterface->expects($this->any())->method(self::SET_COLLECT_SHIPPING_RATES)->willReturnSelf();
        $this->addressInterface->expects($this->any())->method(self::SET_SHIPPING_METHOD)->willReturn(self::TX_FLAT);
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_SHIPPING_CARRIER_CODE)
        ->willReturn('TX');
        $this->totalsInformationInterface->expects($this->any())->method(self::GET_SHIPPING_METHOD_CODE)
        ->willReturn('Flat');
        $this->cartTotalRepository->expects($this->any())->method('get')->with($cartId)->willReturnSelf();
        $this->cartTotalRepository->expects($this->any())->method(self::SET_SUBTOTAL)->willReturn(true);
        $this->cartTotalRepository->expects($this->any())->method(self::SET_GRANDTOTAL)->willReturn(true);

        $this->model->calculate($cartId, $this->totalsInformationInterface);
    }
}
