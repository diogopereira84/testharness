<?php

namespace Fedex\PatchData\Patch\Data;

use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SalesSequence\Model\MetaFactory;
use Magento\SalesSequence\Model\ResourceModel\Meta as ResourceMetadata;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;

class ClearSequenceTables implements DataPatchInterface
{
    public function __construct(
        protected StoreCollectionFactory $storeCollectionFactory,
        protected AppResource $appResource,
        protected MetaFactory $metaFactory,
        protected ResourceMetadata $resourceMetadata
    ) {}

    /**
     * Clean up sequence tables for stores that are not in the store table
     *
     * @return array
     */
    public function apply(): array
    {
        $existingStores = $this->getExistingStoreIds();

        $metadataIds = $this->getMetadataIdsByStoreIdExceptExistingStores($existingStores);
        $profileIds = $this->getProfileIdsByMetadataIds($metadataIds);

        $this->deleteProfiles($profileIds);
        $deletedMetadaAndDropTables = $this->deleteMetadataAndDropTables($metadataIds);
        return $deletedMetadaAndDropTables;
    }

    private function getExistingStoreIds(): array
    {
        $existingStores = [];
        $storeCollection = $this->storeCollectionFactory->create();
        foreach ($storeCollection->getItems() as $store) {
            $existingStores[] = $store->getId();
        }

        if (!in_array(0, $existingStores, true)) {
            $existingStores[] = 0;
        }

        return $existingStores;
    }

    private function getMetadataIdsByStoreIdExceptExistingStores(array $storeIds): array
    {
        $connection = $this->appResource->getConnection('sales');
        $select = $connection->select()->from(
            $this->appResource->getTableName('sales_sequence_meta'),
            ['meta_id']
        )->where(
            'store_id NOT IN (?)', $storeIds
        );

        return $connection->fetchCol($select);
    }

    private function getProfileIdsByMetadataIds(array $metadataIds): array
    {
        $connection = $this->appResource->getConnection('sales');
        $select = $connection->select()
            ->from(
                $this->appResource->getTableName('sales_sequence_profile'),
                ['profile_id']
            )->where('meta_id IN (?)', $metadataIds);

        return $connection->fetchCol($select);
    }

    private function deleteProfiles(array $profileIds): void
    {
        $this->appResource->getConnection('sales')->delete(
            $this->appResource->getTableName('sales_sequence_profile'),
            ['profile_id IN (?)' => $profileIds]
        );
    }

    /**
     *  Delete metadata and drop sequence tables
     *
     * @param array $metadataIds
     * @return array
     */
    private function deleteMetadataAndDropTables(array $metadataIds): array
    {
        $deletedMetadaAndDropTables = [];
        foreach ($metadataIds as $metadataId) {
            $metadata = $this->metaFactory->create();
            try {
                $this->resourceMetadata->load($metadata, $metadataId);
                if (!$metadata->getId()) {
                    continue;
                }

                $this->appResource->getConnection('sales')->dropTable(
                    $metadata->getSequenceTable()
                );
                $deletedMetadaAndDropTables[] = sprintf(
                    'Sequence table %s has been dropped',
                    $metadata->getSequenceTable()
                );
                $this->resourceMetadata->delete($metadata);
            } catch (\Exception $e) {
                $deletedMetadaAndDropTables[] = sprintf('Error deleting metadata: %s', $e->getMessage());
            }
        }

        return $deletedMetadaAndDropTables;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
