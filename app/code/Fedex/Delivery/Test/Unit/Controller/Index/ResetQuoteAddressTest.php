<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Delivery\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\Controller\ResultFactory;
use Fedex\Delivery\Controller\Index\ResetQuoteAddress;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class ResetQuoteAddressTest extends TestCase
{
    protected $resultFactoryMock;
    protected $resetQuoteAddress;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CartFactory|MockObject
     */
    protected $cartFactory;

    /**
     * @var FXORate|MockObject
     */
    protected $fxoRate;

    /**
     * @var FXORateQuote|MockObject
     */
    protected $fxoRateQuote;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var LoggerInterface $loggerMock
     */
    protected $loggerMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create', 'getQuote', 'save', 'getShippingAddress', 'getBillingAddress', 'setFirstname', 'setLastname', 'setStreet', 'setCity', 'setRegion', 'setRegionId', 'setPostcode', 'setTelephone', 'setShippingMethod', 'setShippingDescription', 'setShippingAddress'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRate = $this->getMockBuilder(FXORate::class)
            ->setMethods(['isEproCustomer', 'getFXORate'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->fxoRateQuote = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->resetQuoteAddress = $this->objectManagerHelper->getObject(
            ResetQuoteAddress::class,
            [
                'fxoRateQuote' => $this->fxoRateQuote,
                'cartFactory' => $this->cartFactory,
                'fxoRate' => $this->fxoRate,
                'resultFactory' => $this->resultFactoryMock,
                'logger' => $this->loggerMock
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecute()
    {   
        $this->cartFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('getQuote')
            ->willReturnSelf();
        $this->testResetShippingAddress();
        $this->testResetBillingAddress();
        $this->cartFactory->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->fxoRate->expects($this->once())
            ->method('isEproCustomer')
            ->willReturn(false);
        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->willReturn([]);

        $this->testHandleApiResponse();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->resetQuoteAddress->execute());
    }

    /**
     * Test execute with RateQuote
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithRate()
    {   
        $this->cartFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('getQuote')
            ->willReturnSelf();
        $this->testResetShippingAddress();
        $this->testResetBillingAddress();
        $this->cartFactory->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        
        $this->fxoRate->expects($this->once())
            ->method('isEproCustomer')
            ->willReturn(true);
        $this->fxoRate->expects($this->once())
            ->method('getFXORate')
            ->willReturn([]);
        $this->testHandleApiResponse();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->resetQuoteAddress->execute());
    }

    /**
     * Test execute with exception
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteWithException()
    {   
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->cartFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('getQuote')
            ->willReturnSelf();
        $this->testResetShippingAddress();
        $this->testResetBillingAddress();
        $this->cartFactory->expects($this->once())
            ->method('save')
            ->willThrowException($exception);
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->willReturnSelf();
        $this->fxoRate->expects($this->once())
            ->method('isEproCustomer')
            ->willReturn(false);
        $this->fxoRateQuote->expects($this->once())
            ->method('getFXORateQuote')
            ->willReturn([]);

        $this->testHandleApiResponse();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->resetQuoteAddress->execute());
    }

    /**
     * Test handleApiResponse
     */
    public function testHandleApiResponse()
    {
        $apiResponse = [
            'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
            'output' => [
                'rateQuote' => [
                    'currency' => 'USD',
                    'rateQuoteDetails' => [
                        0 => [ 
                            'totalDiscountAmount' => '($0.05)',
                            'netAmount' => '$20.53',
                            'taxableAmount' => '$20.53',
                            'taxAmount' => '$0.04',
                            'totalAmount' => '$20.57',
                            'estimatedVsActual' => 'ACTUAL',
                        ]
                    ],
                ],
            ],
        ];

        $expectedResult = [
            'netAmount' => '$20.53',
            'taxAmount' => '$0.04',
            'shippingAmount' => NULL
        ];

        $this->assertEquals($expectedResult,
            $this->resetQuoteAddress->handleApiResponse($apiResponse, 'rateQuote', 'rateQuoteDetails'));
    }

    /**
     * Test resetShippingAddress
     */
    public function testResetShippingAddress()
    {
        $this->cartFactory->expects($this->any())
            ->method('getShippingAddress')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setFirstname')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setLastname')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setStreet')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setCity')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setRegion')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setRegionId')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setPostcode')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setTelephone')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setShippingMethod')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setShippingDescription')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setShippingAddress')
            ->willReturnSelf();
        $this->assertNull($this->resetQuoteAddress->resetShippingAddress($this->cartFactory));
    }

    /**
     * Test resetBillingAddress
     */
    public function testResetBillingAddress()
    {
        $this->cartFactory->expects($this->any())
            ->method('getBillingAddress')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setFirstname')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setLastname')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setStreet')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setCity')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setRegion')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setRegionId')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setPostcode')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setTelephone')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setShippingMethod')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setShippingDescription')
            ->willReturnSelf();
        $this->cartFactory->expects($this->any())
            ->method('setShippingAddress')
            ->willReturnSelf();
        $this->assertNull($this->resetQuoteAddress->resetBillingAddress($this->cartFactory));
    }
}
