<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
 
namespace Fedex\Orderhistory\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Ui\Component\Listing\Column\Name;
use Fedex\Orderhistory\Helper\Data;

class NameTest extends \PHPUnit\Framework\TestCase
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
    protected $nameMock;
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

        $this->nameMock = $this->objectManager->getObject(
            Name::class,
            [
				'helperData' => $this->helperDataMock,
				'context' => $this->contextInterfaceMock
            ]
        );
    }
    
    public function testPrepare()
    {
		 $this->contextInterfaceMock->expects($this->any())->method('getProcessor')->willReturn($this->processorMock);
		 $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
		 $expectedResult = $this->nameMock->prepare();
		 $this->assertNull($expectedResult);
    }
}
