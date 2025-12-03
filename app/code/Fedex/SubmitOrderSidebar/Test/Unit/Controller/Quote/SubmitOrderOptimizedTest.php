<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Controller\Quote;

use Fedex\Recaptcha\Model\Validator;
use Fedex\SubmitOrderSidebar\Model\UnifiedDataLayer\DataSourceComposite;
use Fedex\SubmitOrderSidebar\Controller\Quote\SubmitOrderOptimized;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\CartFactory;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;

class SubmitOrderOptimizedTest extends TestCase
{
    protected $dataSourceCompositeMock;
    /**
     * @var (MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var SubmitOrderOptimized
     */
    protected $submitOrderOptimized;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var SubmitOrderBuilder
     */
    protected $submitOrderBuilder;

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var CartFactory $cartFactory
     */
    protected $cartFactory;

    /**
     * @var UploadToQuoteViewModel $uploadToQuoteViewModel
     */
    protected $uploadToQuoteViewModel;

    /** @var Validator|(Validator&object&MockObject)|(Validator&MockObject)|(object&MockObject)|MockObject  */
    protected $recaptchaValidator;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParam'])
            ->addMethods(['getPost'])
            ->getMockForAbstractClass();
        $this->submitOrderBuilder = $this->createMock(SubmitOrderBuilder::class);
        $this->dataSourceCompositeMock = $this->createMock(DataSourceComposite::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->cartFactory = $this->createMock(CartFactory::class);
        $this->uploadToQuoteViewModel = $this->createMock(UploadToQuoteViewModel::class);
        $this->recaptchaValidator = $this->createMock(Validator::class);
        $this->submitOrderOptimized = new SubmitOrderOptimized(
            $this->resultJsonFactory,
            $this->request,
            $this->submitOrderBuilder,
            $this->dataSourceCompositeMock,
            $this->loggerMock,
            $this->toggleConfig,
            $this->cartFactory,
            $this->uploadToQuoteViewModel,
            $this->recaptchaValidator
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $pickupStore = 0;
        $resultJson = $this->createMock(Json::class);
        $requestData = '{"data": "test"}';
        $response = ['some_response'];
        $unifiedDataLayer = ['some_response'];

        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('pickstore')
            ->willReturn($pickupStore);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('data')
            ->willReturn($requestData);
        $this->submitOrderBuilder->expects($this->once())
            ->method('build')
            ->with(json_decode($requestData), $pickupStore)
            ->willReturn(['some_response']);
        $resultJson->expects($this->once())
            ->method('setData')
            ->with([$response, 'unified_data_layer' => $unifiedDataLayer])
            ->willReturn($resultJson);
        $this->dataSourceCompositeMock->expects($this->once())
            ->method('compose')
            ->with($response)->willReturn($response);

        $this->assertEquals($resultJson, $this->submitOrderOptimized->execute());
    }

    public function testExecuteWithRecaptchaError()
    {
        $resultJson = $this->createMock(Json::class);
        $recaptchaError = ['error' => 'Recaptcha validation failed'];

        $this->toggleConfig->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_b2384493')
            ->willReturn(true);
        $this->recaptchaValidator->expects($this->once())
            ->method('isRecaptchaEnabled')
            ->with(SubmitOrderOptimized::CHECKOUT_SUBMIT_ORDER_RECAPTCHA)
            ->willReturn(true);
        $this->recaptchaValidator->expects($this->once())
            ->method('validateRecaptcha')
            ->with(SubmitOrderOptimized::CHECKOUT_SUBMIT_ORDER_RECAPTCHA)
            ->willReturn($recaptchaError);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);
        $resultJson->expects($this->once())
            ->method('setData')
            ->with($recaptchaError)
            ->willReturn($resultJson);

        $this->loggerMock->expects($this->atMost(2))
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('Request data for  order submission SubmitOrderOptimized => ')],
                [$this->stringContains('Submit Order is not working: Recaptcha Error')]
            );

        $this->assertEquals($resultJson, $this->submitOrderOptimized->execute());
    }

    public function testExecuteWithoutRecaptchaError()
    {
        $pickupStore = 0;
        $resultJson = $this->createMock(Json::class);
        $requestData = '{"data": "test"}';
        $response = ['some_response'];
        $unifiedDataLayer = ['some_response'];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['tiger_b2384493'],
                ['mazegeek_team_utoq_quote_deactive_fix']
            )
            ->willReturn(false);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('pickstore')
            ->willReturn($pickupStore);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('data')
            ->willReturn($requestData);
        $this->submitOrderBuilder->expects($this->once())
            ->method('build')
            ->with(json_decode($requestData), $pickupStore)
            ->willReturn($response);
        $resultJson->expects($this->once())
            ->method('setData')
            ->with([$response, 'unified_data_layer' => $unifiedDataLayer])
            ->willReturn($resultJson);
        $this->dataSourceCompositeMock->expects($this->once())
            ->method('compose')
            ->with($response)
            ->willReturn($unifiedDataLayer);

        $this->assertEquals($resultJson, $this->submitOrderOptimized->execute());
    }

    public function testExecuteWithException()
    {
        $resultJson = $this->createMock(Json::class);
        $exceptionMessage = 'Test exception';
        $requestData = '{"data": "test"}';


        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Request data for  order submission SubmitOrderOptimized => '));
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($resultJson);
        $resultJson->expects($this->once())
            ->method('setData')
            ->with([null])
            ->willReturn($resultJson);
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('pickstore')
            ->willReturn(0);
        $this->request->expects($this->once())
            ->method('getPost')
            ->with('data')
            ->willReturn($requestData);
        $this->submitOrderBuilder->expects($this->once())
            ->method('build')
            ->willThrowException(new \Exception($exceptionMessage));
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Exception during checkout submission => ' . $exceptionMessage));

        $this->assertEquals($resultJson, $this->submitOrderOptimized->execute());
    }
}
