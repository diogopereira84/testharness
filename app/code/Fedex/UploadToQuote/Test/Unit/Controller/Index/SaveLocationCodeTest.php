<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Controller\Index;

use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\CartFactory;
use Magento\Framework\Controller\ResultFactory;
use Fedex\UploadToQuote\Controller\Index\SaveLocationCode;
use Fedex\UploadToQuote\Model\Config;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\UploadToQuote\Helper\LocationApiHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\Shipto\Model\ProductionLocation;
use Magento\Framework\Controller\Result\JsonFactory;

class SaveLocationCodeTest extends TestCase
{
    protected $resultFactoryMock;
    protected $companyHelper;
    protected $productionLocationFactoryMock;
    /**
     * @var (\Fedex\Shipto\Model\ProductionLocation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $productionLocation;
    protected $jsonFactoryMock;
    protected $saveLocationCode;
    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var CartFactory|MockObject
     */
    protected $cartFactory;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var UploadToQuoteViewModel|MockObject
     */
    protected $uploadToQuoteViewModelMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var LocationApiHelper|MockObject
     */
    protected $locationApiHelperMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configMock;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create', 'getQuote', 'save', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->uploadToQuoteViewModelMock = $this->getMockBuilder(UploadToQuoteViewModel::class)
            ->setMethods(['getUploadToQuoteConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setData'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPostValue', 'getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->locationApiHelperMock = $this->getMockBuilder(LocationApiHelper::class)
            ->setMethods(['getHubCenterCodeByState'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId', 'isSiteLevelQuoteToggle'])
            ->getMockForAbstractClass();

        $this->productionLocationFactoryMock = $this->getMockBuilder(ProductionLocationFactory::class)
            ->setMethods(['create'
            ,'getCollection','addFieldToFilter','getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productionLocation = $this->getMockBuilder(ProductionLocation::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getData','addFieldToFilter'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create','setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['isTk4673962ToggleEnabled'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->saveLocationCode = $this->objectManagerHelper->getObject(
            SaveLocationCode::class,
            [
                'cartFactory' => $this->cartFactory,
                'logger' => $this->loggerMock,
                'uploadToQuoteViewModel' => $this->uploadToQuoteViewModelMock,
                'resultFactory' => $this->resultFactoryMock,
                '_request' => $this->requestMock,
                'locationApiHelper' => $this->locationApiHelperMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'productionLocationFactory'=> $this->productionLocationFactoryMock,
                'companyHelper'=>$this->companyHelper,
                'config' => $this->configMock
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
        $response = [
            'output' => [
                'search' => [
                    [
                        'officeLocationId' => '0639'
                    ]
                ]
            ]
        ];
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn(['OH','US']);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn(0);
        $this->configMock->expects($this->any())
            ->method('isTk4673962ToggleEnabled')
            ->willReturn(true);
        $this->uploadToQuoteViewModelMock->expects($this->once())
            ->method('getUploadToQuoteConfigValue')
            ->willReturn("test_api_url");
        $this->locationApiHelperMock->expects($this->once())
            ->method('getHubCenterCodeByState')
            ->willReturn($response);
        $this->cartFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('getQuote')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('save')
            ->willReturnSelf();
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultFactoryMock);
        $this->resultFactoryMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->companyHelper->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(71);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->testRecommended();
        $this->assertIsObject($this->saveLocationCode->execute());
    }

    /**
     * Test execute with error case.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testExecuteApiError()
    {
        $response = [
            'errors' => [
                [
                    'code' => 'ADDRESS.ADDRESSCLASSIFICATIONS.INVALID.PROPERTY',
                    'message' => 'The addressClassifications does not exist'
                ]
            ]
        ];
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn(['OH','US']);
        $this->uploadToQuoteViewModelMock->expects($this->once())
            ->method('getUploadToQuoteConfigValue')
            ->willReturn("test_api_url");
        $this->locationApiHelperMock->expects($this->once())
            ->method('getHubCenterCodeByState')
            ->willReturn($response);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultFactoryMock);
        $this->resultFactoryMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->testRecommended();
        $this->assertIsObject($this->saveLocationCode->execute());
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

        $response = [
            'output' => [
                'search' => [
                    [
                        'officeLocationId' => '0639'
                    ]
                ]
            ]
        ];
        $this->requestMock->expects($this->any())
            ->method('getPostValue')
            ->willReturn(['OH','US']);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('id')
            ->willReturn(0);
        $this->configMock->expects($this->any())
            ->method('isTk4673962ToggleEnabled')
            ->willReturn(true);
        $this->uploadToQuoteViewModelMock->expects($this->once())
            ->method('getUploadToQuoteConfigValue')
            ->willReturn("test_api_url");
        $this->locationApiHelperMock->expects($this->once())
            ->method('getHubCenterCodeByState')
            ->willReturn($response);
        $this->cartFactory->expects($this->once())
            ->method('create')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('getQuote')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->cartFactory->expects($this->once())
            ->method('save')
            ->willThrowException($exception);
        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultFactoryMock);
        $this->resultFactoryMock->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
            $this->productionLocationFactoryMock->expects($this->any())
            ->method('getCollection')
            ->willReturnSelf();
        $this->testRecommended();
        $this->assertIsObject($this->saveLocationCode->execute());
    }

    /**
     * Test With Recommeded and Restricted Store
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testRecommended()
    {
        $companyId=71;
        $locationData = ['id' => 1, 'location_id' => '023', 'location_name' => 'test'];
        $this->productionLocationFactoryMock->expects($this->any())
        ->method('create')
        ->willReturnSelf();
        $this->productionLocationFactoryMock->expects($this->any())
        ->method('getCollection')
        ->willReturnSelf();
        $this->productionLocationFactoryMock->expects($this->any())
        ->method('addFieldToFilter')
        ->willReturnSelf();
        $this->productionLocationFactoryMock->expects($this->any())
        ->method('getData')
        ->willReturn($locationData);
        $this->assertNotNull ($this->saveLocationCode->getRecommendedLocations($companyId));
    }
}
