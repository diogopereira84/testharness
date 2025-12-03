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
use Fedex\ExpressCheckout\Controller\Index\UpdateFXORate;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Cart;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\FXOPricing\Helper\FXORate;
use Magento\Framework\App\RequestInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Model\FXORateQuote;

/**
 * Prepare test objects.
 */
class UpdateFXORateTest extends TestCase
{
    protected $cartMock;
    protected $quote;
    protected $toggleConfig;
    protected $updateFXORateMock;
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
     * @var FXORate|fxoRateHelperMock
     */
    private $fxoRateHelperMock;

    /**
     * @var RequestInterface|requestMock
     */
    private $requestMock;

    /**
     * @var CartDataHelper|cartDataHelperMock
     */
    private $cartDataHelperMock;

    /**
     * @var Session|customerSessionMock
     */
    private $customerSessionMock;

    /**
     * @var MockObject|FXORateQuote
     */
    protected $fxoRateQuoteMock;

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
        $this->jsonMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->fxoRateHelperMock = $this->getMockBuilder(FXORate::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORate', 'getFXORateQuote']) // Add 'getFXORateQuote'
            ->getMock();
        $this->fxoRateQuoteMock = $this->getMockBuilder(FXORateQuote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFXORateQuote'])
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->cartDataHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['decryptData'])
            ->getMock();
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProfileSession'])
            ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->updateFXORateMock = $this->objectManagerHelper->getObject(
            UpdateFXORate::class,
            [
                'context' => $this->contextMock,
                'jsonFactory' => $this->jsonMock,
                'logger' => $this->loggerMock,
                'cartFactory' => $this->cartFactoryMock,
                'fxoRate' => $this->fxoRateHelperMock,
                'fxoRateQuote' => $this->fxoRateQuoteMock,
                'request' => $this->requestMock,
                'cartDataHelper' => $this->cartDataHelperMock,
                'customerSession' => $this->customerSessionMock,
                'toggleConfig' => $this->toggleConfig
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
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getData')
            ->willReturn(323453453);
        $this->fxoRateHelperMock->expects($this->any())
            ->method('getFXORate')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('success');

        $this->assertIsObject($this->updateFXORateMock->execute());
    }

    /**
     * @test Test Execute function
     *
     * @return void
     */
    public function testExecuteWithToggle()
    {
        $profileInfo = '{
            "output":{
                "profile":{
                    "contact":{
                        "personName":{
                            "firstName":"Attri",
                            "lastName":"Kumar"
                        },
                        "emailDetail": {
                            "emailAddress": "attri.kumar@infogain.com"
                        },
                        "phoneNumberDetails": [
                            {
                                "phoneNumber": {
                                    "number": "3243243433"
                                }
                            }
                        ]
                    }
                }
            }
        }';
        $profileInfo = json_decode($profileInfo);
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getData')
            ->willReturn(323453453);
        $this->fxoRateHelperMock->expects($this->any())
            ->method('getFXORate')
            ->willReturnSelf();
        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->any())
            ->method('getProfileSession')
            ->willReturn($profileInfo);
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('success');


        $this->assertNotNull($this->updateFXORateMock->execute());
    }

    /**
     * @test Test Execute function with Exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);
        $this->fxoRateHelperMock->expects($this->any())
            ->method('getFXORate')
            ->willThrowException($exception);
        $this->jsonMock->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->willReturn('success');

        $this->assertIsObject($this->updateFXORateMock->execute());
    }

    /**
     * @test Test Execute function with Exception
     *
     * @return void
     */
    public function testExecuteWithRegionLookup(): void
    {
        // NEW: Expect the cartFactory to create a cart.
        $this->cartFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->cartMock);

        // NEW: Expect the cart mock to return a quote.
        $this->cartMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quote);

        // Simulate POST values for locationId and fedexAccount.
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturnMap([
                ['locationId', 'loc123'],
                ['fedexAccount', null]
            ]);

        // Simulate request params with a regionId and empty stateOrProvinceCode.
        $ccFormData = [
            'regionId' => '169',
            'stateOrProvinceCode' => ''
        ];
        $this->requestMock->expects($this->any())
            ->method('getParams')
            ->willReturn($ccFormData);

        // Simulate that the quote contains an encrypted fedex_account_number.
        $this->quote->expects($this->any())
            ->method('getData')
            ->with('fedex_account_number')
            ->willReturn('encryptedAccount');

        // Simulate decryption of the account number.
        $this->cartDataHelperMock->expects($this->once())
            ->method('decryptData')
            ->with('encryptedAccount')
            ->willReturn('account123');

        // Simulate no profile session.
        $this->customerSessionMock->expects($this->any())
            ->method('getProfileSession')
            ->willReturn(null);

        // NEW: Force an exception in the FXO rate call to trigger the catch block.
        $exception = new \Exception('Test exception');
        // MODIFY: Set expectation on fxoRateQuoteMock (not fxoRateHelperMock).
        $this->fxoRateQuoteMock->expects($this->once())
            ->method('getFXORateQuote')
            ->with($this->quote)
            ->willThrowException($exception);

        // Expect JSON result setData() to be called with ["success" => false].
        $this->jsonMock->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($data) {
                return $data === ["success" => false];
            }))
            ->willReturnSelf();

        $result = $this->updateFXORateMock->execute();
        $this->assertSame($this->jsonMock, $result);
    }
}
