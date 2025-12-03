<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Test\Unit\Model\Export;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Fedex\CustomerExportEmail\Model\Export\ExportInfoFactory;
use Magento\Framework\Serialize\SerializerInterface;
use \Psr\Log\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;
use Fedex\CustomerExportEmail\Api\Data\ExportInfoInterface;


class ExportInfoFactoryTest extends \PHPUnit\Framework\TestCase
{
    protected $serializeMock;
    protected $exportInfoInterfaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $objectManagerMock;
    /** @var ObjectManager |MockObject */
    protected $objectManagerHelper;

    /** @var ExportInfo |MockObject */
    protected $exportinfo;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->serializeMock = $this->getMockBuilder(SerializerInterface::class)
                                    ->setMethods(['serialize'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->exportInfoInterfaceMock = $this->getMockBuilder(ExportInfoInterface::class)
                                    ->setMethods(['setMessage','setCustomerdata','setInActiveColumns'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
                                    ->setMethods(['critical'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
                                    ->setMethods(['create'])
                                    ->disableOriginalConstructor()
                                    ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->exportinfo = $this->objectManagerHelper->getObject(
            ExportInfoFactory::class,
            [
                'objectManager' => $this->objectManagerMock,
                'logger' => $this->loggerMock,
                'serializer' => $this->serializeMock
            ]
        );
    }

    /**
     * Test testGetMessage
     */
    public function testCreate()
    {
        $this->objectManagerMock->expects($this->any())->method('create')->willReturn($this->exportInfoInterfaceMock);
        $this->exportInfoInterfaceMock->expects($this->any())->method('setMessage')->willReturnSelf();
        $this->exportInfoInterfaceMock->expects($this->any())->method('setCustomerdata')->willReturnSelf();
        $this->exportInfoInterfaceMock->expects($this->any())->method('setInActiveColumns')->willReturnSelf();
        $this->serializeMock->expects($this->any())->method('serialize')->willReturnSelf();
        $this->assertEquals($this->exportInfoInterfaceMock, $this->exportinfo->create('Test','test','test'));
    }

}
