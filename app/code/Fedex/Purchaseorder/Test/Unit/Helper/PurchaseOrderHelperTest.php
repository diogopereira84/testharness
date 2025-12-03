<?php

/**
 * Php file,Test case for PurchaseOrderHelper.
 *
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Purchaseorder\Test\Unit\Helper;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DataObject;
use Fedex\Purchaseorder\Helper\PurchaseOrderHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class PurchaseOrderHelperTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Fedex\Company\Model\AdditionalDataFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $additionalDataFactoryMock;
    protected $storeManagerMock;
    /**
     * @var (\Fedex\Company\Api\Data\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterfaceMock;
    protected $ondemandConfigInterfaceMock;
    protected $storeInterfaceMock;
    protected $purchaseOrderHelperMock;
    /**
     * @var LoggerInterface
     */
    protected $loggerMock;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->additionalDataFactoryMock = $this
            ->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->configInterfaceMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->ondemandConfigInterfaceMock = $this->getMockBuilder(OndemandConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
            ->setMethods([
                'getId',
                'setId',
                'getCode',
                'setCode',
                'getName',
                'setName',
                'getWebsiteId',
                'setWebsiteId',
                'getStoreGroupId',
                'setIsActive',
                'getIsActive',
                'setStoreGroupId',
                'getExtensionAttributes',
                'setExtensionAttributes'
            ])
            ->disableoriginalConstructor()
            ->getMock();

        $this->purchaseOrderHelperMock = $this->objectManager->getObject(
            PurchaseOrderHelper::class,
            [
                'additionalDataFactory'     => $this->additionalDataFactoryMock,
                'logger'                    => $this->loggerMock,
                'storeManager'              => $this->storeManagerMock,
                'configInterface'           => $this->configInterfaceMock,
                'ondemandConfigInterface'   => $this->ondemandConfigInterfaceMock
            ]
        );
    }//end setUp()

    /**
     * Test Case for setStoreCode.
     */
    public function testsetStoreCode()
    {
        $companyId = 1;
        $storeId = 65;
        $storeCode = 'l6site51';

        $this->ondemandConfigInterfaceMock->expects($this->any())
            ->method('getB2bDefaultStore')
            ->willReturn($storeId);

        $this->storeManagerMock->expects($this->any())->method('getStore')
            ->with($storeId)
            ->willReturn($this->storeInterfaceMock);

        $this->storeInterfaceMock->expects($this->atMost(2))
            ->method('getCode')
            ->willReturn($storeCode);

        $this->assertEquals($storeCode, $this->purchaseOrderHelperMock->getStoreCode($companyId));
    }
}//end class
