<?php

namespace Fedex\ProductCustomAtrribute\Observer;

use Fedex\ProductCustomAtrribute\Observer\SaveDltData;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class SaveDltDataTest extends TestCase
{
    protected $requestMock;
    protected $eventMock;
    protected $product;
    protected $saveDltDataMock;
    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getPost'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getDataObject'])
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);

        $this->saveDltDataMock = $objectManagerHelper->getObject(
            SaveDltData::class,
            [
                'request' => $this->requestMock,
            ]
        );
    }

    /**
     * @test execute
     */
    public function testExecute()
    {
        $postData = [
            'product' => [
                'dlt_threshold' => 'test_data',
            ],
        ];
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn($postData);

        $eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventObserver->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->product->expects($this->any())
            ->method('setData')->willReturnSelf();
        $this->eventMock->expects($this->once())
            ->method('getDataObject')
            ->willReturn($this->product);
        $this->assertEquals(null, $this->saveDltDataMock->execute($eventObserver));
    }

    /**
     * @test execute
     */
    public function testExecuteNull()
    {
        $postData = [
            'product' => [
                'dlt_threshold' => null,
            ],
        ];
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn($postData);

        $eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventObserver->expects($this->once())->method('getEvent')->willReturn($this->eventMock);
        $this->product->expects($this->any())
            ->method('setData')->with('dlt_thresholds', null)->willReturnSelf();
        $this->eventMock->expects($this->once())
            ->method('getDataObject')
            ->willReturn($this->product);
        $this->assertNull($this->saveDltDataMock->execute($eventObserver));
    }
}
