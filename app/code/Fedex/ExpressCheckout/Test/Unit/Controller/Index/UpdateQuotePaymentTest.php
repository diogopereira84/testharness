<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ExpressCheckout\Test\Unit\Controller;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\ExpressCheckout\Controller\Index\UpdateQuotePayment;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Cart;
use Fedex\ExpressCheckout\Helper\ExpressCheckout;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

/**
 * Prepare test objects.
 */
class UpdateQuotePaymentTest extends TestCase
{
    protected $cartMock;
    protected $quote;
    protected $updateQuotePaymentMock;
    private const ERROR_EXCEPTION_MESSAGE = 'Error in update payment information in quote';

    private const PROFILE_ADDRESS = [
        'firstName' => 'Nidhi',
        'lastName' => 'Singh',
        'email' => 'nidhi.singh@infogain.com',
        'phoneNumber' => '8888888888'
    ];

    private const CREDIT_CARD = [
        'billingAddress' =>
        [
            'stateOrProvinceCode' => 'TX',
            'countryCode' => 'US',
            'streetLines' => ['plano', 'city'],
            'postalCode' => '75024',
            'city' => 'planos'
        ]
    ];

    /**
     * @var ObjectManagerHelper|MockObject
     */
    private $objectManagerHelper;

    /**
     * @var Context|contextMock
     */
    private $contextMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var JsonFactory|jsonMock
     */
    private $jsonMock;

    /**
     * @var CartFactory|cartFactoryMock
     */
    private $cartFactoryMock;

    /**
     * @var ExpressCheckout|expressCheckoutHelperMock
     */
    private $expressCheckoutHelperMock;

    /**
     * @var RequestInterface|MockObject
     */
    private $requestMock;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->jsonMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();
        $this->expressCheckoutHelperMock = $this->getMockBuilder(ExpressCheckout::class)
            ->setMethods(['setPaymentInformation'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->updateQuotePaymentMock = $this->objectManagerHelper->getObject(
            UpdateQuotePayment::class,
            [
                'context' => $this->contextMock,
                'jsonFactory' => $this->jsonMock,
                'logger' => $this->loggerMock,
                'cartFactory' => $this->cartFactoryMock,
                'expressCheckoutHelper' => $this->expressCheckoutHelperMock,
                '_request' => $this->requestMock
            ]
        );
    }

    /**
     * @test Test Execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->exactly(3))
           ->method('getPost')
           ->willReturnOnConsecutiveCalls(
               $this->returnValue(static::CREDIT_CARD),
               $this->returnValue('fedexaccount'),
               $this->returnValue(static::PROFILE_ADDRESS)
           );
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->expressCheckoutHelperMock
            ->expects($this->once())
            ->method('setPaymentInformation')
            ->willReturnSelf();
        $this->quote->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('success');

        $this->assertIsObject($this->updateQuotePaymentMock->execute());
    }

    /**
     * @test Test Execute function with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->requestMock->expects($this->exactly(3))
           ->method('getPost')
           ->willReturnOnConsecutiveCalls(
               $this->returnValue(static::CREDIT_CARD),
               $this->returnValue('fedexaccount'),
               $this->returnValue(static::PROFILE_ADDRESS)
           );
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->expressCheckoutHelperMock
            ->expects($this->once())
            ->method('setPaymentInformation')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('critical');
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('error');

        $this->assertIsObject($this->updateQuotePaymentMock->execute());
    }
}
