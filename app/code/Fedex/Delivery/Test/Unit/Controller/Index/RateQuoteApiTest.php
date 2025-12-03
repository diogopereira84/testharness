<?php

/**
 * Php file for test case of RateAPI.
 *
 * @author  Infogain <Team_Explorer@infogain.com>
 * @license Reserve For fedEx
 */
namespace Fedex\Delivery\Test\Unit\Controller\Index;

use Magento\Framework\App\Action\Context;
use Fedex\Delivery\Controller\Index\RateQuoteApi;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Checkout\Model\CartFactory;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\FXOPricing\Model\FXORateQuote;

class RateQuoteApiTest extends TestCase
{
    protected $context;
    protected $cartFactoryMock;
    protected $cartMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $quoteMock;
    protected $cartDataHelperMock;
    /**
     * @var (\Fedex\FXOPricing\Model\FXORateQuote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fxoRateQuote;
    protected $requestMock;
    protected $rateQuoteApi;
    public const LOCATION_ID = '5432';
    public const REQ_PICK_TIME = '2023-05-13T17:00:00';
    public const FEDEX_ACC_NO = '653243324';
    public const DEC_FEDEX_ACC_NO = '0:3:7D96hHguKjU5P5m3jYOJmrD1+1Pw0AKypX5QYs0JgrR0NIjZg==';
    public const OUTPUT_WITH_RATEQUOTE = [
            'output' => [
                'rateQuote' => [
                    'currency'    => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines'        => [
                                0 => [
                                    'instanceId'            => 0,
                                    'productId'             => '1508784838900',
                                    'retailPrice'           => '$0.9',
                                    'discountAmount'        => '$0.0',
                                    'unitQuantity'          => 1,
                                    'linePrice'             => '$0.479',
                                    'priceable'             => 1,
                                    'productLineDetails'    => [
                                        0 => [
                                            'detailCode'                => '0173',
                                            'description'               => 'Single Sided Color',
                                            'detailCategory'            => 'PRINTING',
                                            'unitQuantity'              => 1,
                                            'unitOfMeasurement'         => 'EACH',
                                            'detailPrice'               => '$0.79',
                                            'detailDiscountPrice'       => '$0.70',
                                            'detailUnitPrice'           => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.08',
                                        ],
                                    ],
                                    'productRetailPrice'    => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice'      => '0.49',
                                    'editable'              => '',
                                ],
                            ],
                            'grossAmount'         => '$0.9',
                            'discounts'           => [],
                            'totalDiscountAmount' => '$0.005',
                            'netAmount'           => '$0.59',
                            'taxableAmount'       => '$0.69',
                            'taxAmount'           => '$0.0',
                            'totalAmount'         => '$0.59',
                            'estimatedVsActual'   => 'ACTUAL',
                        ],
                    ],

                ],
            ],
        ];

    /**
     * Description Creating variable for defining the constuctor
     * {@inheritdoc}
     *
     * @var $objectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * Creating the Mock.
     *
     * @author  Infogain <Team_Explorer@infogain.com>
     * @license Reserve For fedEx
     * @return  MockBuilder
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartFactoryMock = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(
                [
                    'getData',
                    'setData',
                    'setLocationId',
                    'setCustomerPickupLocationData',
                    'setIsFromPickup',
                    'setFedExAccountNumber'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods(['decryptData','encryptData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRateQuote = $this->getMockBuilder(FXORateQuote::class)
            ->setMethods(['getFXORateQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();
        $this->context->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->objectManagerHelper  = new ObjectManagerHelper($this);
        $this->rateQuoteApi = $this->objectManagerHelper->getObject(
            RateQuoteApi::class,
            [
                'context'           => $this->context,
                'cartFactory'       => $this->cartFactoryMock,
                'logger'            => $this->loggerMock,
                'cartDataHelper'    => $this->cartDataHelperMock,
                'fxoRateQuote'      => $this->fxoRateQuote
            ]
        );
    }

    /**
     * Test case for setPickupData
     */
    public function testsetPickupData()
    {
        $this->quoteMock->expects($this->any())->method('setCustomerPickupLocationData')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('setIsFromPickup')->willReturn($this->quoteMock);
        $this->assertNull(
            $this->rateQuoteApi->setPickupData($this->quoteMock, static::LOCATION_ID, static::FEDEX_ACC_NO)
        );
    }

    /**
     * Test case for Execute
     */
    public function testExecute()
    {
        $this->cartFactoryMock->expects($this->any())->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->requestMock->expects($this->any())->method('getParam')
            ->willReturn(static::REQ_PICK_TIME);
        $this->requestMock->expects($this->any())->method('getParam')
            ->willReturn(static::LOCATION_ID);
        $this->assertNull($this->rateQuoteApi->execute());
    }

    /**
     * Test case for GetFedexAccountNumber
     */
    public function testGetFedexAccountNumber()
    {
        $this->quoteMock->expects($this->any())->method('getData')->with('fedex_account_number')
            ->willReturn(static::FEDEX_ACC_NO);
        $this->cartDataHelperMock->expects($this->any())->method('decryptData')
            ->willReturn(static::DEC_FEDEX_ACC_NO);
        $this->assertEquals(static::DEC_FEDEX_ACC_NO, $this->rateQuoteApi->getFedexAccountNumber($this->quoteMock));
    }

    /**
     * Test case for Execute with Exception
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->cartFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->assertNull($this->rateQuoteApi->execute());
    }
}
