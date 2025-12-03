<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Model;

use Fedex\MarketplaceCheckout\Model\DataProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Mirakl\Core\Model\ResourceModel\Shop\Collection as ShopCollection;
use Mirakl\Core\Model\ResourceModel\Shop\CollectionFactory as ShopCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class DataProviderTest extends TestCase
{
    /**
     * @var ShopCollectionFactory|MockObject
     */
    private $shopCollectionFactoryMock;

    /**
     * @var Json|MockObject
     */
    private $jsonMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShopCollection|MockObject
     */
    private $shopCollectionMock;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->shopCollectionFactoryMock = $this->createMock(ShopCollectionFactory::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->shopCollectionMock = $this->getMockBuilder(ShopCollection::class)
            ->disableOriginalConstructor()
            ->addMethods(['getId'])
            ->onlyMethods(['getFirstItem', 'getData'])
            ->getMock();

        $this->shopCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->shopCollectionMock);

        $this->dataProvider = new DataProvider(
            'data_provider_name',
            'id_field_name',
            'request_field_name',
            $this->shopCollectionFactoryMock,
            $this->loggerMock,
            $this->jsonMock
        );
    }

    /**
     * Test getData method
     */
    public function testGetData()
    {
        $shopId = 123;
        $shopData = [
            'id' => $shopId,
            'shipping_methods' => '[{"method": "method_1"}, {"method": "method_2"}]',
            'shipping_method' => [
                ['method' => 'method_1'],
                ['method' => 'method_2']
            ]
        ];

        $this->shopCollectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturnSelf();

        $this->shopCollectionMock->expects($this->once())
            ->method('getData')
            ->willReturn($shopData);

        $this->shopCollectionMock->expects($this->any())
            ->method('getId')
            ->willReturn('123');

        $this->jsonMock->expects($this->once())
            ->method('unserialize')
            ->with($shopData['shipping_methods'])
            ->willReturn([
                ['method' => 'method_1'],
                ['method' => 'method_2']
            ]);

        $result = $this->dataProvider->getData();

        $this->assertArrayHasKey($shopId, $result);
        $this->assertEquals($shopData, $result[$shopId]);
        $this->assertEquals([['method' => 'method_1'], ['method' => 'method_2']], $result[$shopId]['shipping_method']);
    }

    /**
     * Test getData method with loaded data
     */
    public function testGetDataWithLoadedData()
    {
        $loadedData = ['some' => 'loaded', 'data' => 'here'];
        $this->setProperty($this->dataProvider, 'loadedData', $loadedData);

        $result = $this->dataProvider->getData();

        $this->assertSame($loadedData, $result);
    }

    /**
     * Set a private or protected property on an object
     *
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    private function setProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
