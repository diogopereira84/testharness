<?php
declare(strict_types=1);
namespace Fedex\Catalog\Model;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Fedex\CatalogMigration\Model\Entity\Attribute\Source\SharedCatalogOptions;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Fedex\Catalog\Api\AttributeHandlerInterface;
use Magento\Framework\Exception\InputException;

/**
 * Model Class AttributeHandler
 */
class AttributeHandler implements AttributeHandlerInterface
{   
    /**
     * Attribute code for shared catalogs.
     */
    private const ATTRIBUTE_CODE = 'shared_catalogs';

    /**
     * AttributeHandler Constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeOptionInterfaceFactory $attributeOptionFactory
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param SharedCatalogOptions $sharedCatalogOptions
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private AttributeRepositoryInterface $attributeRepository,
        private AttributeOptionInterfaceFactory $attributeOptionFactory,
        private AttributeOptionManagementInterface $attributeOptionManagement,
        private SharedCatalogOptions $sharedCatalogOptions,
        private ResourceConnection $resourceConnection
    ) {}

    /**
     * Retrieve options for a given attribute ID.
     *
     * @param string $attributeId 
     * @return array exist attribute options list
     */
    public function getAttributeOptions(string $attributeId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $optionsTable = $this->resourceConnection->getTableName('eav_attribute_option');
        $optionsValueTable = $this->resourceConnection->getTableName('eav_attribute_option_value');

        $select = $connection->select()
            ->from(['o' => $optionsTable], [])
            ->join(['ov' => $optionsValueTable], 'o.option_id = ov.option_id', ['value' => 'ov.value'])
            ->where('o.attribute_id = ?', $attributeId)
            ->where('ov.store_id = ?', 0);

        return $connection->fetchAll($select);
    }

    /**
     * Retrieve the attribute ID based on the entity type and attribute code.
     *
     * @param string $entityType The entity type code 'catalog_product'
     * @param string $attributeCode
     * @return string attribute Id
     */
    public function getAttributeIdByCode(string $entityType, string $attributeCode): string
    {
        $attributeCollection = $this->attributeRepository->get($entityType, $attributeCode);

        return $attributeCollection->getAttributeId();
    }

    /**
     * Retrieve all shared catalog ids and label
     * @return array
     */
    public function getAllSharedCatalogOptions(): array
    {
        return $this->sharedCatalogOptions->getAllOptions();
    }

    /**
     * Get New Shared catalogs options id array list
     *
     * @param string $attributeId 
     * @throws \Exception If an error occurs while adding attribute options.
     * @return array
     */
    public function getNewSharedCatalogOptions(string $attributeId): array
    {
        try {
            // Retrieve options for shared catalog and attribute
            $sharedCatalogOptions = $this->getAllSharedCatalogOptions();
            $sharedCatalogAttributeOptions = $this->getAttributeOptions($attributeId);

            // Convert options to associative arrays
            $sharedCatalogOptionsMap = $this->convertToLabelValueMap($sharedCatalogOptions);
            $sharedCatalogAttributeOptionsMap = $this->convertToLabelValueMap($sharedCatalogAttributeOptions);
            
            // Find non-matching options
            $nonMatchingOptions = array_diff($sharedCatalogOptionsMap, $sharedCatalogAttributeOptionsMap);

            return $nonMatchingOptions;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Adds new attribute options for a specific entity.
     *
     * @param array|null $sharedCatalogNewOptions
     * @throws \Exception
     * @return void
     * @codeCoverageIgnore
     */
    public function addAttributeOption(array $sharedCatalogNewOptions = null): void
    {
        try {
            $attributeId = $this->getAttributeIdByCode(Product::ENTITY, static::ATTRIBUTE_CODE);
            $attributeOptionsLength = $this->sharedCatalogAttributeOptionLength($attributeId);

            if ($sharedCatalogNewOptions === null) {
                $sharedCatalogNewOptions = $this->getNewSharedCatalogOptions($attributeId);
            }

            foreach ($sharedCatalogNewOptions as $option) {
                $attributeOption = $this->attributeOptionFactory->create();

                $attributeOption->setAttributeId($attributeId);
                $attributeOption->setLabel((string) $option);
                $attributeOption->setSortOrder($attributeOptionsLength++);

                $this->attributeOptionManagement->add(
                    Product::ENTITY,
                    $attributeId,
                    $attributeOption,
                    true
                );
            }
        } catch (\Exception $e) {
            throw new InputException(__( __METHOD__ . ':' . __LINE__ .'Error creating attribute options:' . $e->getMessage()));
        }
    }

    /**
     * Retrieve the length of existing attribute options for a shared catalog attribute
     *
     * @param string $attributeId
     * @return int The length of existing attribute options.
     */
    public function sharedCatalogAttributeOptionLength(string $attributeId): int
    {
        $options = $this->getAttributeOptions($attributeId);

        if (is_array($options) || $options instanceof \Countable) {
            return count($options);
        }
    }

    /**
     * Converts an array of options into a key-value map
     *
     * @param array $options
     * @return array
     */
    public function convertToLabelValueMap(array $options): array
    {
        $result = [];
        foreach ($options as $optionItem) {
            $result[$optionItem['value']] = (string) $optionItem['value'];
        }
        return $result;
    }
}
