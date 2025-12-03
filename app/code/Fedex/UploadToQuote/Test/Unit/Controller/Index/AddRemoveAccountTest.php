<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use Fedex\UploadToQuote\Controller\Index\AddRemoveAccount;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Cart;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class AddRemoveAccountTest extends TestCase
{
    protected $request;
    protected $quote;
    protected $cart;
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuoteHelper;
    /**
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartFactory $cartFactory
     */
    protected $cartFactory;

    /**
     * @var CartDataHelper $cartDataHelper
     */
    protected $cartDataHelper;

    /**
     * @var FXORateQuote $fxoRateQuote
     */
    protected $fxoRateQuote;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var ObjectManagerHelper $objectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var AddRemoveAccount $addRemoveAccount
     */
    protected $addRemoveAccount;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getRequest', 'getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['encryptData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods([
                'setAppliedFedexAccNumber',
                'setAccountDiscountExist',
                'getAccountDiscountExist',
                'unsAccountDiscountExist',
                'setRemoveFedexAccountNumber',
                'setRemoveFedexAccountNumberWithSi',
            ])->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cart = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->fxoRateQuoteHelper = $this->getMockBuilder(FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->addRemoveAccount = $this->objectManagerHelper->getObject(
            AddRemoveAccount::class,
            [
                'request' => $this->request,
                'cartFactory' => $this->cartFactory,
                'cartDataHelper' => $this->cartDataHelper,
                'checkoutSession' => $this->checkoutSession,
                'fxoRateQuoteHelper' => $this->fxoRateQuoteHelper,
                'jsonFactory' => $this->jsonFactory,
                'cart' => $this->cart,
            ]
        );
    }

    /**
     * Test execute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->request->expects($this->any())->method('getRequest')->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')->willReturn(1234234);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('setData')->willReturnSelf();
        $this->cartDataHelper->expects($this->once())->method('encryptData')->willReturn('3125151');
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();
         
        $this->assertIsObject($this->addRemoveAccount->execute());
    }

    /**
     * Test execute with else
     *
     * @return void
     */
    public function testExecuteWithElse()
    {
        $this->request->expects($this->any())->method('getRequest')->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')->willReturn(false);
        $this->cartFactory->expects($this->once())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('setData')->willReturnSelf();
        $this->checkoutSession->expects($this->once())->method('getAccountDiscountExist')->willReturn(1);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();
         
        $this->assertIsObject($this->addRemoveAccount->execute());
    }

    /**
     * Test execute with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->request->expects($this->any())->method('getRequest')->willReturnSelf();
        $this->request->expects($this->any())->method('getParam')->willReturn(false);
        $this->cartFactory->expects($this->once())->method('create')->willThrowException($exception);
        $this->jsonFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->once())->method('setData')->willReturnSelf();
        
        $this->assertIsObject($this->addRemoveAccount->execute());
    }
}
