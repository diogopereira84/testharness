<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\Orderhistory\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Ui\Component\Listing\Column\EntityId;
use Fedex\Orderhistory\Helper\Data;

class EntityIdTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\UiComponentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $uiComponentFactoryMock;
    protected $contextInterfaceMock;
    protected $processorMock;
    protected $helperDataMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $entityIdMock;
    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->uiComponentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
											->disableOriginalConstructor()
												->getMock();

        $this->contextInterfaceMock = $this->getMockBuilder(ContextInterface::class)
										->disableOriginalConstructor()->setMethods(['getProcessor'])
											->getMockForAbstractClass();
        
        $this->processorMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
										->disableOriginalConstructor()->setMethods(['register'])
											->getMockForAbstractClass();
        
        $this->helperDataMock = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
									->disableOriginalConstructor()
										->setMethods(['isModuleEnabled'])
										->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->entityIdMock = $this->objectManager->getObject(
            EntityId::class,
            [
				'helperData' => $this->helperDataMock,
				'context' => $this->contextInterfaceMock
            ]
        );
    }
    public function testPrepareDataSource(){
        $this->entityIdMock->setData('name','entity_id');
        
        $dataSource['data']['items'][] = ['entity_id'=>'123']; 
        $this->assertIsArray($this->entityIdMock->prepareDataSource($dataSource));
    }
    public function testPrepare()
    {
		 $this->contextInterfaceMock->expects($this->any())->method('getProcessor')->willReturn($this->processorMock);
		 $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(false);
		 $expectedResult = $this->entityIdMock->prepare();
		 $this->assertNull($expectedResult);
    }
}
