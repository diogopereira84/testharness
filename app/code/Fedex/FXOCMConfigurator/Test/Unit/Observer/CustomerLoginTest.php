<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\ObserverInterface;
use Fedex\FXOCMConfigurator\Helper\Batchupload;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOCMConfigurator\Observer\CustomerLogin;
use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Customer;

class CustomerLoginTest extends TestCase
{
    protected $toggleConfigMock;
    protected $batchuploadMock;
    /**
     * @var (\Magento\Framework\Event\ObserverInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $observerInterfaceMock;
    protected $observerMock;
    protected $eventMock;
    protected $customer;
    protected $fxocmObserver;
    protected $toggleConfig;
    protected $batchupload;


    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->batchuploadMock = $this->getMockBuilder(Batchupload::class)
            ->setMethods(['updateUserworkspaceDataAfterLogin', 'getUserWorkspaceSessionValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observerInterfaceMock = $this->getMockBuilder(ObserverInterface::class)
            ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getEvent', 'getCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        
        


        $this->fxocmObserver = $objectManager->getObject(
            CustomerLogin::class,
            [
                'toggleConfig' => $this->toggleConfigMock,
                'batchupload' => $this->batchuploadMock,
            ]
        );
    }

    
    public function testExecute()
    {
        $this->observerMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn($this->eventMock);

        $this->eventMock
            ->expects($this->any())
            ->method('getCustomer')
            ->willReturn($this->customer);

        $this->customer
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $this->batchuploadMock
            ->expects($this->any())
            ->method('getUserWorkspaceSessionValue')
            ->willReturn('test');
        
        $this->toggleConfigMock
            ->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        
        $this->batchuploadMock
            ->expects($this->any())
            ->method('updateUserworkspaceDataAfterLogin')
            ->willReturnSelf();
            

        $result = $this->fxocmObserver->execute($this->observerMock);
        
        $this->assertNull($result);
       
    }

    
}
