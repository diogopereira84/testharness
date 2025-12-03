<?php

namespace Fedex\EnhancedProfile\Test\Unit\Controller\CreditCard;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnhancedProfile\ViewModel\CompanyPaymentData;
use Fedex\EnhancedProfile\Controller\CreditCard\RemoveSharedCreditCardInfo;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Exception;

class RemoveSharedCreditCardInfoTest extends TestCase
{
    protected $requestMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    protected $companyPaymentData;
    protected $additionalDataFactoryMock;
    protected $additionalDataMock;
    protected $additionalDataCollectionMock;
    protected $jsonFactoryMock;
    protected $jsonMock;
    protected $toggleConfigMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $removeInfoMock;
    protected function setUp(): void
    {
        $context = $this->createMock(Context::class);

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->companyPaymentData = $this->createMock(CompanyPaymentData::class);

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCollection',
                'setData',
                'save',
                'isEmpty',
                'setCcToken',
                'setCcData',
                'setCcTokenExpiryDateTime'
            ])->getMock();

        $this->additionalDataCollectionMock = $this->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem'])
            ->getMock();

        $this->jsonFactoryMock = $this->getMockBuilder(JsonFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->removeInfoMock = $this->objectManager->getObject(
            RemoveSharedCreditCardInfo::class,
            [
                'context' => $context,
                'logger' => $this->logger,
                'jsonFactory' => $this->jsonFactoryMock,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'companyPaymentData' => $this->companyPaymentData,
                'request' => $this->requestMock,
                'toggleConfig' => $this->toggleConfigMock,

            ]
        );
    }

    public function testExecute()
    {
        $companyData = json_decode('companyId', 48);
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($companyData);

        $this->companyPaymentData->expects($this->any())->method('getCompanyDataById')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('isEmpty')->willReturn(false);
        $this->additionalDataMock->expects($this->any())->method('setCcToken')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('setCcData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('setCcTokenExpiryDateTime')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('save')->willReturnSelf();
        $this->testUncheckNonEditablePaymentMethod();
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->assertNotNull($this->removeInfoMock->execute());
    }

    public function testExecuteWithException()
    {
        $exception = new Exception;
        $companyData = json_decode('companyId', 48);
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($companyData);
        $this->companyPaymentData->expects($this->any())->method('getCompanyDataById')->willThrowException($exception);
        $this->jsonFactoryMock->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->removeInfoMock->execute());
    }

    public function testUncheckNonEditablePaymentMethod()
    {
        $companyId = 48;
        $this->additionalDataFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollectionMock);
        $this->additionalDataCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollectionMock->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalDataMock);
        $this->additionalDataMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('save')->willReturnSelf();

        $this->assertTrue($this->removeInfoMock->uncheckNonEditablePaymentMethod($companyId));
    }

    public function testUncheckNonEditablePaymentMethodWithException()
    {
        $companyId = 48;
        $exception = new Exception;
        $this->additionalDataFactoryMock->expects($this->any())->method('create')->willThrowException($exception);

        $this->assertNotNull($this->removeInfoMock->uncheckNonEditablePaymentMethod($companyId));
    }
}
