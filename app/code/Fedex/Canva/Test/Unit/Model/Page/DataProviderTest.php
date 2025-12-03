<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Model\Page;

use Fedex\Canva\Model\Builder;
use Fedex\Canva\Model\SizeCollection;
use Fedex\Canva\Model\Page\DataProvider;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected DataProvider $dataProviderMock;
    protected SerializerInterface|MockObject $serializerMock;
    protected Builder|MockObject $builderMock;
    protected PageRepositoryInterface|MockObject $pageRepositoryMock;
    protected PageFactory|MockObject $pageFactoryMock;
    protected RequestInterface|MockObject $requestMock;
    protected SizeCollection|MockObject $sizeCollectionMock;
    protected PageInterface|MockObject $pageInterfaceMock;

    protected function setUp(): void
    {
        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->onlyMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->builderMock = $this->getMockBuilder(Builder::class)
            ->onlyMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageRepositoryMock = $this->getMockBuilder(PageRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getParam'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sizeCollectionMock = $this->getMockBuilder(SizeCollection::class)
            ->onlyMethods(['toArray', 'getDefaultOptionId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pageInterfaceMock = $this->getMockBuilder(PageInterface::class)
            ->setMethods(['getData', 'getId', 'getCustomLayoutUpdateXml'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->dataProviderMock = $this->objectManager->getObject(
            DataProvider::class,
            [
                'serializer' => $this->serializerMock,
                'builder' => $this->builderMock,
                'pageRepository' => $this->pageRepositoryMock,
                'pageFactory' => $this->pageFactoryMock,
                'request' => $this->requestMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetData(): void
    {
        $defaultId = 2;
        $canvaSizeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"TEST","display_width":"TEST","display_height":"test","orientation":"test","is_default":true}]';

        $reflection = new \ReflectionClass(DataProvider::class);

        $getCurrentPageMethod = $reflection->getMethod('getCurrentPage');
        $getCurrentPageMethod->setAccessible(true);

        $getPageIdMethod = $reflection->getMethod('getPageId');
        $getPageIdMethod->setAccessible(true);

        $canvaSizesUnserialized = json_decode($canvaSizeValue);

        $this->requestMock->expects($this->once())->method('getParam')->with('')->willReturn($defaultId);

        $this->pageInterfaceMock->expects($this->once())->method('getData')->willReturn(['id' => $defaultId, 'canva_sizes' => $canvaSizeValue]);
        $this->pageInterfaceMock->expects($this->atMost(2))->method('getId')->willReturn($defaultId);
        $this->pageInterfaceMock->expects($this->once())->method('getCustomLayoutUpdateXml')->willReturn(true);
        $this->pageRepositoryMock->expects($this->once())->method('getById')->with($defaultId)->willReturn($this->pageInterfaceMock);

        $this->serializerMock->expects($this->atMost(5))->method('unserialize')->with($canvaSizeValue)->willReturn($canvaSizesUnserialized);
        $this->builderMock->expects($this->atMost(5))->method('build')->with($canvaSizesUnserialized)->willReturn($this->sizeCollectionMock);
        $this->sizeCollectionMock->expects($this->atMost(5))->method('toArray')->willReturn([]);

        $this->dataProviderMock->getData();
    }
}
