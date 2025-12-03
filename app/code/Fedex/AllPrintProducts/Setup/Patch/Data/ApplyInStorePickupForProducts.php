<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\AllPrintProducts\Setup\Patch\Data;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @codeCoverageIgnore
 */
class ApplyInStorePickupForProducts implements DataPatchInterface
{
    private const IN_STORE_PICKUP_ATTRIBUTE_CODE = 'in_store_pickup';
    private const IN_STORE_PICKUP_AVAILABLE = 'Available';
    private const IN_STORE_PICKUP_NOT_AVAILABLE = 'Not Available';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Action $productAction
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private Action $productAction,
        protected CollectionFactory $productCollectionFactory,
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $stores = $this->storeManager->getStores(true);
        $productIdsForAvailableOption = $this->getProductsForInStorePickupAvaialable();
        $availableOptionId = $this->getInStorePickupAvailableOption();
        foreach ($stores as $storeId => $store) {
            $this->productAction->updateAttributes(
                $productIdsForAvailableOption,
                ['in_store_pickup' => $availableOptionId],
                $storeId
            );
        }

        $productIdsForNotAvailableOption = $this->getProductsForInStorePickupNotAvaialable();
        $notAvailableOptionId = $this->getInStorePickupNotAvailableOption();
        foreach ($stores as $storeId => $store) {
            $this->productAction->updateAttributes(
                $productIdsForNotAvailableOption,
                ['in_store_pickup' => $notAvailableOptionId],
                $storeId
            );
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [UpdateInStorePickUp::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @param EavSetup $eavSetup
     * @return array
     */
    private function getProductsForInStorePickupAvaialable()
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter('mirakl_mcm_product_id', ['null' => true]);

        return $productCollection->getColumnValues('entity_id');
    }

    /**
     * @param EavSetup $eavSetup
     * @return array
     */
    private function getProductsForInStorePickupNotAvaialable()
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToFilter('mirakl_mcm_product_id', ['notnull' => true]);

        return $productCollection->getColumnValues('entity_id');
    }

    private function getInStorePickupAvailableOption()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select();
        $select->reset();
        $select->from(
            ['eaov' => 'eav_attribute_option_value'],
            'eaov.option_id'
        )
            ->joinInner(
                ['eao' => 'eav_attribute_option'],
                'eao.option_id = eaov.option_id',
                ''
            )
            ->joinInner(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = eao.attribute_id',
                ''
            )
            ->where('eaov.value = ?', self::IN_STORE_PICKUP_AVAILABLE)
            ->where('ea.attribute_code = ?', self::IN_STORE_PICKUP_ATTRIBUTE_CODE);

        $value = $connection->fetchOne($select);

        return $value !== false ? $value : 0;
    }

    private function getInStorePickupNotAvailableOption()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select();
        $select->reset();
        $select->from(
            ['eaov' => 'eav_attribute_option_value'],
            'eaov.option_id'
        )
            ->joinInner(
                ['eao' => 'eav_attribute_option'],
                'eao.option_id = eaov.option_id',
                ''
            )
            ->joinInner(
                ['ea' => 'eav_attribute'],
                'ea.attribute_id = eao.attribute_id',
                ''
            )
            ->where('eaov.value = ?', self::IN_STORE_PICKUP_NOT_AVAILABLE)
            ->where('ea.attribute_code = ?', self::IN_STORE_PICKUP_ATTRIBUTE_CODE);

        $value = $connection->fetchOne($select);

        return $value !== false ? $value : 0;
    }
}
