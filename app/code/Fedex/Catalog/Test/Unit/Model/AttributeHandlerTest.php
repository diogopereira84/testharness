<?php
declare(strict_types=1);

namespace Fedex\Catalog\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Catalog\Model\AttributeHandler;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Fedex\CatalogMigration\Model\Entity\Attribute\Source\SharedCatalogOptions;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AttributeHandlerTest extends TestCase
{
    protected $attributeRepositoryMock;
    protected $attributeOptionFactoryMock;
    protected $attributeOptionManagementMock;
    protected $sharedCatalogOptionsMock;
    protected $resourceConnectionMock;
    protected $attributeHandler;

    protected function setUp(): void
    {
        $this->attributeRepositoryMock = $this->createMock(AttributeRepositoryInterface::class);
        $this->attributeOptionFactoryMock = $this->createMock(AttributeOptionInterfaceFactory::class);
        $this->attributeOptionManagementMock = $this->createMock(AttributeOptionManagementInterface::class);
        $this->sharedCatalogOptionsMock = $this->getMockBuilder(SharedCatalogOptions::class)
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->setMethods(['getTableName', 'getConnection'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeHandler = new AttributeHandler(
            $this->attributeRepositoryMock,
            $this->attributeOptionFactoryMock,
            $this->attributeOptionManagementMock,
            $this->sharedCatalogOptionsMock,
            $this->resourceConnectionMock
        );

        $objectManagerHelper = new ObjectManager($this);
        $this->attributeHandler = $objectManagerHelper->getObject(
            AttributeHandler::class,
            [
                "attributeRepository" => $this->attributeRepositoryMock,
                "attributeOptionFactory" => $this->attributeOptionFactoryMock,
                "attributeOptionManagement" => $this->attributeOptionManagementMock,
                "sharedCatalogOptions" => $this->sharedCatalogOptionsMock,
                "resourceConnection" => $this->resourceConnectionMock
           ]
        );
    }

    /*
    * GetAttributeOptions
    */
    public function getAttributeOptions(): void
    {
        $attributeOptions = [['value' => 'option1'], ['value' => 'existing']];
        $attributeId = '123';

        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceConnectionMock->expects($this->exactly(1))
            ->method('getConnection')
            ->willReturn($connectionMock);

        $optionsTable = 'eav_attribute_option';
        $optionsValueTable = 'eav_attribute_option_value';
        $this->resourceConnectionMock->expects($this->exactly(2))
            ->method('getTableName')
            ->withConsecutive([$optionsTable], [$optionsValueTable])
            ->willReturnOnConsecutiveCalls($optionsTable, $optionsValueTable);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $connectionMock->expects($this->any())
                    ->method('select')
                    ->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('from')
            ->with(['o' => $optionsTable], [])
            ->willReturnSelf();

        $selectMock->expects($this->any())
            ->method('join')
            ->with(['ov' => $optionsValueTable], 'o.option_id = ov.option_id', ['value' => 'ov.value'])
            ->willReturnSelf();

        $selectMock->expects($this->any())
            ->method('where')
            ->withConsecutive(
                ['o.attribute_id = ?', $attributeId],
                ['ov.store_id = ?', 0]
            )
            ->willReturnSelf();

        $connectionMock->expects($this->any())
            ->method('fetchAll')
            ->with($selectMock)
            ->willReturn($attributeOptions);
    }

    /*
    * GetAttributeNoOptions
    */
    public function getAttributeNoOptions(): void
    {
        $attributeOptions = [];
        $attributeId = '123';

        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceConnectionMock->expects($this->exactly(1))
            ->method('getConnection')
            ->willReturn($connectionMock);

        $optionsTable = 'eav_attribute_option';
        $optionsValueTable = 'eav_attribute_option_value';
        $this->resourceConnectionMock->expects($this->exactly(2))
            ->method('getTableName')
            ->withConsecutive([$optionsTable], [$optionsValueTable])
            ->willReturnOnConsecutiveCalls($optionsTable, $optionsValueTable);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $connectionMock->expects($this->any())
                    ->method('select')
                    ->willReturn($selectMock);

        $selectMock->expects($this->any())
            ->method('from')
            ->with(['o' => $optionsTable], [])
            ->willReturnSelf();

        $selectMock->expects($this->any())
            ->method('join')
            ->with(['ov' => $optionsValueTable], 'o.option_id = ov.option_id', ['value' => 'ov.value'])
            ->willReturnSelf();

        $selectMock->expects($this->any())
            ->method('where')
            ->withConsecutive(
                ['o.attribute_id = ?', $attributeId],
                ['ov.store_id = ?', 0]
            )
            ->willReturnSelf();

        $connectionMock->expects($this->any())
            ->method('fetchAll')
            ->with($selectMock)
            ->willReturn($attributeOptions);
    }

    /*
    * Test getNewSharedCatalogOptions
    */
    public function testGetNewSharedCatalogOptions(): void
    {
        $attributeId = '123';
        $sharedCatalogOptions = [
            ['label' => 'option1', 'value' => 'option1'],
            ['label' => 'Option 2', 'value' => 'Option 2']
        ];
        $attributeOptions = [['value' => 'option1'], ['value' => 'existing']];

        $this->sharedCatalogOptionsMock->expects($this->once())
            ->method('getAllOptions')
            ->willReturn($sharedCatalogOptions);

        $this->getAttributeOptions();
        
        $result = $this->attributeHandler->getNewSharedCatalogOptions($attributeId);
        $this->assertEquals([ 'Option 2' => 'Option 2' ], $result);
    }

    public function testGetAttributeIdByCode()
    {
        $attributeId = '123';
        $attributeMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\AbstractAttribute::class);

        $attributeMock->expects($this->any())->method('getAttributeId')->willReturn($attributeId);
        $this->attributeRepositoryMock->method('get')->willReturn($attributeMock);
        $this->assertEquals($attributeId,  $this->attributeHandler->getAttributeIdByCode(Product::ENTITY, 'shared_catalogs'));

    }

    /**
     * Test getNewSharedCatalogOptionsException
     *
     * @return void
     */
    public function testGetNewSharedCatalogOptionsException(): void
    {
        $attributeId = '123';

        $this->sharedCatalogOptionsMock->expects($this->once())
            ->method('getAllOptions')
            ->willThrowException(new \Exception('Simulated error'));
        
        $result = $this->attributeHandler->getNewSharedCatalogOptions($attributeId);
        $this->assertEquals([], $result);
    }

    /**
     * test sharedCatalogAttributeOptionLength
     *
     * @return void
     */
    public function testSharedCatalogAttributeOptionLength(): void
    {
        // Mock necessary data for the test
        $attributeId = '123';
        $this->getAttributeOptions();
        $result = $this->attributeHandler->sharedCatalogAttributeOptionLength($attributeId);

        $this->assertEquals(2, $result);
    }

    /**
     * test sharedCatalogAttributeOptionNoLength
     *
     * @return void
     */
    public function testSharedCatalogAttributeOptionNoLength(): void
    {
        $attributeId = '123';
        $this->getAttributeNoOptions();
        $result = $this->attributeHandler->sharedCatalogAttributeOptionLength($attributeId);

        $this->assertEquals(0, $result);
    }
}
