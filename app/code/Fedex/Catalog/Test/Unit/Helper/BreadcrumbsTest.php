<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Helper;

use Fedex\Catalog\Helper\Breadcrumbs;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\ScopeConfigInterface;

class BreadcrumbsTest extends TestCase
{

	protected $writerInterfaceMock;
 /**
  * @var (\Magento\Framework\App\Cache\TypeListInterface & \PHPUnit\Framework\MockObject\MockObject)
  */
 protected $typeListInterfaceMock;
 /**
  * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
  */
 protected $managerInterfaceMock;
 /**
  * @var (\Magento\Framework\App\State & \PHPUnit\Framework\MockObject\MockObject)
  */
 protected $stateMock;
 protected $scopeConfigInterfaceMock;
 protected $breadcrumbsHelper;
 protected function setUp(): void
    {

    	$this->writerInterfaceMock = $this
            ->getMockBuilder(WriterInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMockForAbstractClass();

        $this->typeListInterfaceMock = $this
            ->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->managerInterfaceMock = $this
            ->getMockBuilder(ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stateMock = $this
            ->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigInterfaceMock = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->breadcrumbsHelper = $objectManagerHelper->getObject(
            Breadcrumbs::class,
            [
                'configWriter' => $this->writerInterfaceMock,
                'cacheTypeList' => $this->typeListInterfaceMock,
                'messageManager' => $this->managerInterfaceMock,
        		'state' => $this->stateMock
            ]
        );
    }

    /**
     * testGetControlJson 
     * 
     */
    public function testGetControlJson()
    {
        $output = '';

        $this->scopeConfigInterfaceMock
		        ->expects($this->any())		        
		        ->method('getValue')
		        ->willReturn($output);

		$this->assertEquals($output,$this->breadcrumbsHelper->getControlJson());
    }

    /**
     * testSetControlJson 
     * 
     */
    public function testSetControlJson()
    {
    	$this->writerInterfaceMock
    			->expects($this->any())
    			->method('save')
    			->willReturnSelf();
    	$this->assertEquals(null,$this->breadcrumbsHelper->setControlJson('test'));
    }

}