<?php
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Setup\Patch\Data;

use Fedex\SharedCatalogCustomization\Setup\Patch\Data\AddLegacyCatalogRootFolderIdCategoryAttribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Setup\Module\Setup;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AddLegacyCatalogRootFolderIdCategoryAttributeTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Setup\ModuleDataSetupInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $moduleDataSetupMock;
    /**
     * @var (\Magento\Eav\Setup\EavSetupFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eavSetupFactoryMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var object
     */
    protected $addLegacyCatalogRootFolderIdCategoryAttribute;
    const CONNECTION_NAME = 'connection';
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;
    private Setup $object;
    private AdapterInterface|MockObject $connection;
    private MockObject|ResourceConnection $resourceModel;
    private EavSetup|MockObject $eavSetup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @codeCoverageIgnore
     */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->addLegacyCatalogRootFolderIdCategoryAttribute = $this->objectManager->getObject(
            AddLegacyCatalogRootFolderIdCategoryAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
            ]
        );
    }

    /**
     * testApply()
     *
     * @codeCoverageIgnore
     */
    public function testApply()
    {

        $this->resourceModel = $this->createMock(ResourceConnection::class);
        $this->connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceModel->expects($this->any())
            ->method('getConnection')
            ->with(self::CONNECTION_NAME)
            ->willReturn($this->connection);
        $this->object = new Setup($this->resourceModel, self::CONNECTION_NAME);

        $this->connection->expects($this->once())
            ->method('startSetup');
        $this->object->startSetup();

        $this->eavSetup = $this->createMock(\Magento\Eav\Setup\EavSetup::class);
        $attributeData = [
            'type' => 'varchar',
            'label' => 'Legacy Catalog Root Folder Id',
            'input' => 'text',
            'sort_order' => 333,
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => null,
            'group' => 'General Information',
            'backend' => '',
        ];
        $attributeCode = 'legacy_catalog_root_folder_id';
        $this->eavSetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, $attributeCode, $attributeData);

        $this->eavSetup->expects($this->any())
            ->method('addAttribute')
            ->with(\Magento\Catalog\Model\Category::ENTITY, $attributeCode, $attributeData)
            ->willReturn($this->connection);

        $attribute = $this->getAttribute();

        $endSetup = $this->connection->expects($this->once())
            ->method('endSetup');

        $this->object->endSetup();

        $this->assertEmpty(array_diff($attribute, $attributeData));

    }

    /**
     * testRevert()
     *
     * @codeCoverageIgnore
     */

    public function testRevert()
    {

        $this->resourceModel = $this->createMock(ResourceConnection::class);
        $this->connection = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->resourceModel->expects($this->any())
            ->method('getConnection')
            ->with(self::CONNECTION_NAME)
            ->willReturn($this->connection);
        $this->object = new Setup($this->resourceModel, self::CONNECTION_NAME);

        $this->connection->expects($this->once())
            ->method('startSetup');
        $this->object->startSetup();

        $this->connection->expects($this->once())
            ->method('endSetup');
        $this->object->endSetup();

    }

    /**
     * getAttribute()
     *
     * @codeCoverageIgnore
     */
    public function getAttribute()
    {
        $attribute = [
            'type' => 'varchar',
            'label' => 'Legacy Catalog Root Folder Id',
            'input' => 'text',
            'sort_order' => 333,
            'source' => '',
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => null,
            'group' => 'General Information',
            'backend' => '',
        ];
        return $attribute;
    }

}
