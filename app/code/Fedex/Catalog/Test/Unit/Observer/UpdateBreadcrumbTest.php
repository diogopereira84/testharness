<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Observer;

use Fedex\Catalog\Observer\UpdateBreadcrumb;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Framework\App\Cache\TypeListInterface;
use Fedex\Catalog\Helper\Breadcrumbs as BreadcrumbsHelper;

class UpdateBreadcrumbTest extends TestCase
{

	/**
  * @var (\Magento\Framework\App\Cache\TypeListInterface & \PHPUnit\Framework\MockObject\MockObject)
  */
 protected $typeListInterfaceMock;
 protected $breadcrumbHelperMock;
 protected $observerMock;
 protected $eventMock;
 protected $updateBreadcrumbObserver;
 protected function setUp(): void
    {

    	$this->typeListInterfaceMock = $this
            ->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->breadcrumbHelperMock = $this
            ->getMockBuilder(BreadcrumbsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->updateBreadcrumbObserver = $objectManagerHelper->getObject(
            UpdateBreadcrumb::class,
            [
                'helper' => $this->breadcrumbHelperMock,
                'cacheTypeList' => $this->typeListInterfaceMock
            ]
        );
    }

    /**
     * testExecute 
     * 
     */
    public function testExecute()
    {
        $event = ['status'=>'success','title'=>'Nk Template Test'];

        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $this->observerMock->expects($this->any())
		    ->method('getEvent')
		    ->willReturn($this->eventMock);

        $this->eventMock->expects($this->any())
	        ->method('getData')
	        ->willReturn($event);

        $this->breadcrumbHelperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

		$this->assertEquals(null,$this->updateBreadcrumbObserver->execute($this->observerMock));
    }

    /**
     * testExecutewithFalse 
     * 
     */
    public function testExecutewithFalse()
    {
        $event = ['status'=>'success','title'=>'Test'];

        $control = [['label'=>'Nk Template Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4'],['label'=>'Test','url'=>'nk-template-test','skus'=>'1614105200640-4,1593103993699-4,1594830761054-4,1534434635598-4,1534436209752-2,1592421958159-4']];

        $controlJson = json_encode($control);

        $this->observerMock->expects($this->any())
		    ->method('getEvent')
		    ->willReturn($this->eventMock);
		    
        $this->eventMock->expects($this->any())
	        ->method('getData')
	        ->willReturn($event);

        $this->breadcrumbHelperMock->expects($this->any())
            ->method('getControlJson')
            ->willReturn($controlJson);

		$this->assertEquals(null,$this->updateBreadcrumbObserver->execute($this->observerMock));
    }

}