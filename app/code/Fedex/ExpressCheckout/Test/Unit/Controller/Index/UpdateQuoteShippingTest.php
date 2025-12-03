<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ExpressCheckout\Test\Unit\Controller;

use Fedex\ExpressCheckout\Controller\Index\UpdateQuoteShipping;
use Fedex\ExpressCheckout\Helper\ExpressCheckout;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Prepare test objects.
 */
class UpdateQuoteShippingTest extends TestCase
{
    protected $cartMock;
    protected $quote;
    protected $updateQuoteShippingMock;
    private const ERROR_EXCEPTION_MESSAGE = 'Error in update shipping and billing address in quote';

    private const PROFILE_ADDRESS = [
        'firstName' => 'Nidhi',
        'lastName' => 'Singh',
        'email' => 'nidhi.singh@infogain.com',
        'phoneNumber' => '8888888888',
    ];

    private const LOCATION_ADDRESS = [
        'billing_address' => [
            'region' => 'TX',
            'region_id' => 66,
            'region_code' => 'TX',
            'country_id' => 'US',
            'street' => [
                0 => 'Legacy',
            ],
            'postcode' => '75024',
            'city' => 'Plano',
            'firstname' => 'Nidhi',
            'lastname' => 'Singh',
            'email' => 'nidhi.singh@infogain.com',
            'telephone' => '8888888888',
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
            ->setMethods(['setCustomerInformation', 'prepareShippingData', 'setShippingBillingAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->updateQuoteShippingMock = $this->objectManagerHelper->getObject(
            UpdateQuoteShipping::class,
            [
                'context' => $this->contextMock,
                'jsonFactory' => $this->jsonMock,
                'logger' => $this->loggerMock,
                'cartFactory' => $this->cartFactoryMock,
                'expressCheckoutHelper' => $this->expressCheckoutHelperMock,
                '_request' => $this->requestMock,
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
                $this->returnValue(static::PROFILE_ADDRESS),
                $this->returnValue(static::LOCATION_ADDRESS),
                $this->returnValue(1892)
            );
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->expressCheckoutHelperMock
            ->expects($this->once())
            ->method('setCustomerInformation')
            ->willReturnSelf();
        $this->expressCheckoutHelperMock
            ->expects($this->once())
            ->method('prepareShippingData')
            ->willReturn(static::PROFILE_ADDRESS);
        $this->expressCheckoutHelperMock
            ->expects($this->once())
            ->method('setShippingBillingAddress')
            ->willReturn(static::PROFILE_ADDRESS);
        $this->quote->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('success');

        $this->assertIsObject($this->updateQuoteShippingMock->execute());
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
                $this->returnValue(static::PROFILE_ADDRESS),
                $this->returnValue(static::LOCATION_ADDRESS),
                $this->returnValue(1892)
            );
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->expressCheckoutHelperMock
            ->expects($this->once())
            ->method('setCustomerInformation')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('critical');
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('error');

        $this->assertIsObject($this->updateQuoteShippingMock->execute());
    }
}
