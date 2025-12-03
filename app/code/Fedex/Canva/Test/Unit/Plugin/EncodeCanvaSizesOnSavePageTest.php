<?php

declare(strict_types=1);

namespace Fedex\Canva\Test\Unit\Plugin;

use Fedex\Canva\Model\SizeCollection;
use Fedex\Canva\Model\Builder;
use Fedex\Canva\Model\Size;
use Fedex\Canva\Plugin\EncodeCanvaSizesOnSavePage;
use Magento\Cms\Controller\Adminhtml\Page\Save;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EncodeCanvaSizesOnSavePageTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected EncodeCanvaSizesOnSavePage $encodeCanvaSizesOnSavePageMock;
    protected RequestInterface|MockObject $requestMock;
    protected SerializerInterface|MockObject $serializerMock;
    protected Builder|MockObject $builderMock;
    protected Save|MockObject $saveMock;
    protected SizeCollection|MockObject $sizeCollectionMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->addMethods(['getPostValue', 'setPostValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->serializerMock = $this->getMockBuilder(SerializerInterface::class)
            ->onlyMethods(['unserialize'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->builderMock = $this->getMockBuilder(Builder::class)
            ->onlyMethods(['build'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveMock = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sizeCollectionMock = $this->getMockBuilder(SizeCollection::class)
            ->onlyMethods(['toJson', 'setDefaultOption'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->encodeCanvaSizesOnSavePageMock = $this->objectManager->getObject(
            EncodeCanvaSizesOnSavePage::class,
            [
                'request' => $this->requestMock,
                'serializer' => $this->serializerMock,
                'builder' => $this->builderMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testBeforeExecute(): void
    {
        $canvaSizeValue = '[{"id":"option_0","sort_order":0,"product_mapping_id":"TEST","display_width":"TEST","display_height":"test","orientation":"test","is_default":true}]';
        $canvaSizesUnserialized = json_decode($canvaSizeValue);
        $this->requestMock->expects($this->atMost(2))->method('getPostValue')
            ->withConsecutive(['canva_sizes'], ['default'])->willReturnOnConsecutiveCalls($canvaSizesUnserialized, 'option_0');
        $this->builderMock->expects($this->once())->method('build')->with($canvaSizesUnserialized)->willReturn($this->sizeCollectionMock);

        $this->sizeCollectionMock->expects($this->once())->method('setDefaultOption')->with(0)->willReturnSelf();

        $this->requestMock->expects($this->once())->method('setPostValue')->with()->willReturnSelf();

        $this->encodeCanvaSizesOnSavePageMock->beforeExecute($this->saveMock);
    }
}
