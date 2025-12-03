<?php

namespace Fedex\CatalogMvp\Test\Unit\Observer;

use Fedex\CatalogMvp\Observer\Collectionloadafter;
use Magento\Framework\Event\Observer;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Event;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CollectionloadafterTest extends TestCase
{
    protected $deliveryhelperMock;
    protected $catalogMvpHelperMock;
    protected $observerMock;
    protected $eventMock;
    protected $parentCollection;
    protected $collectionloadafter;
    protected function setUp(): void
    {
        
        $this->deliveryhelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer'])
            ->getMock();

        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpSharedCatalogEnable','isSelfRegCustomerAdmin', 'checkPrintCategory'])
            ->getMock();

        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->parentCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToSelect','addAttributeToFilter'])
            ->getMock();
            
        $objectManagerHelper = new ObjectManager($this);
        $this->collectionloadafter = $objectManagerHelper->getObject(
            Collectionloadafter::class,
            [
                'deliveryhelper' => $this->deliveryhelperMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
            ]
        );
    }

    /**
     * @test testExecute
     */
    public function testExecute()
    {
        $this->deliveryhelperMock->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isMvpSharedCatalogEnable')
            ->willReturn(true);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);

        $this->catalogMvpHelperMock->expects($this->any())
            ->method('checkPrintCategory')
            ->willReturn(false);

        $this->observerMock->expects($this->any())
		    ->method('getEvent')
		    ->willReturn($this->eventMock);

        $this->eventMock->expects($this->any())
	        ->method('getCollection')
	        ->willReturn($this->parentCollection);

        $this->parentCollection->expects($this->any())
	        ->method('addAttributeToSelect')
	        ->willReturnSelf();

        $this->parentCollection->expects($this->any())
	        ->method('addAttributeToFilter')
	        ->willReturnSelf();

        $this->assertNotNull($this->collectionloadafter->execute($this->observerMock));
    }
    
}
