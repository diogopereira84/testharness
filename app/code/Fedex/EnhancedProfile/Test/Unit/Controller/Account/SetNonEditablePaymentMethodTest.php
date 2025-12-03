<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\EnhancedProfile\Controller\Account\SetNonEditablePaymentMethod;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;

/**
 * Test class for SetNonEditablePaymentMethod
 */
class SetNonEditablePaymentMethodTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Action\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $jsonMock;
    protected $additionalDataFactoryMock;
    protected $additionalDataMock;
    protected $additionalDataCollectionMock;
    protected $nonEditablePaymentMethod;
    /**
     * @var JsonFactory|MockObject
     */
    protected $jsonFactory;

    /**
     * @var AdditionalDataFactory|MockObject
     */
    protected $additionalDataFactory;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParams'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['getParams'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->setMethods(['create', 'getSize'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataCollectionMock = $this
            ->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->nonEditablePaymentMethod = $this->objectManager->getObject(
            SetNonEditablePaymentMethod::class,
            [
                'context' => $this->contextMock,
                'jsonFactory' => $this->jsonFactory,
                'logger' => $this->loggerMock,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'request' => $this->requestMock,
            ]
        );
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $data = ['non_editable_payment_method' => 1, 'company_id' => 48];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);

        $this->additionalDataFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollectionMock->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('save')->willReturnSelf();
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->nonEditablePaymentMethod->execute());
    }

    /**
     * Test execute method with exception
     *
     * @return void
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $data = ['non_editable_payment_method' => 1, 'company_id' => 48];
        $this->requestMock->expects($this->any())->method('getParams')->willReturn($data);
        $this->additionalDataFactoryMock->expects($this->any())->method('create')
        ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())->method('getCollection')
        ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->additionalDataCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->additionalDataCollectionMock->expects($this->any())->method('getFirstItem')
        ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())->method('setData')->willReturnSelf();
        $this->additionalDataMock->expects($this->any())->method('save')->willThrowException($exception);
        $this->jsonFactory->expects($this->any())->method('create')->willReturn($this->jsonMock);
        $this->jsonMock->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertNotNull($this->nonEditablePaymentMethod->execute());
    }
}
