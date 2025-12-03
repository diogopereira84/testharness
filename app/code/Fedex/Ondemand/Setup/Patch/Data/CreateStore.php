<?php
namespace Fedex\Ondemand\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\GroupFactory;


class CreateStore implements DataPatchInterface
{
    /**
     * @param GroupFactory $groupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private GroupFactory $groupFactory,
        private ModuleDataSetupInterface $moduleDataSetup
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $newStorecode = "ondemand";
        $newgroupName = "Ondemand";
        /** @var Magento\Store\Model\Website $website */

        $b2bGroup = $this->getB2bStore();

        if ($b2bGroup && $b2bGroup->getWebsiteId() > 0) {
            $groupData[] = [
                'website_id' => $b2bGroup->getWebsiteId(),
                'name' => $newgroupName,
                'code' => $newStorecode,
                'root_category_id' => $b2bGroup->getRootCategoryId(),
                'default_store_id' => $b2bGroup->getDefaultStoreId(),
            ];

            $this->moduleDataSetup->getConnection()->insertArray(
                $this->moduleDataSetup->getTable('store_group'),
                [
                    'website_id',
                    'name',
                    'code',
                    'root_category_id',
                    'default_store_id',
                ],
                $groupData
            );

            $groupId = $this->moduleDataSetup->getConnection()->lastInsertId();

            $storeData[] = [
                'code' => $newStorecode,
                'website_id' => $b2bGroup->getWebsiteId(),
                'group_id' => $groupId,
                'name' => $newgroupName,
                'sort_order' => 0,
                'is_active' => 1,
            ];

            $this->moduleDataSetup->getConnection()->insertArray(
                $this->moduleDataSetup->getTable('store'),
                [
                    'code',
                    'website_id',
                    'group_id',
                    'name',
                    'sort_order',
                    'is_active',
                ],
                $storeData
            );

            $storeId = $this->moduleDataSetup->getConnection()->lastInsertId();

            $storeGroupTable = $this->moduleDataSetup->getTable('store_group');
            $this->moduleDataSetup->getConnection()->update(
                $storeGroupTable,
                ['default_store_id' => $storeId],
                ['group_id = ?' => $groupId]
            );
        }
        $this->moduleDataSetup->endSetup();
    }

    /**
     * return Magento\Store\Model\Group
     */
    public function getB2bStore()
    {
        $group = $this->groupFactory->create(['setup' => $this->moduleDataSetup]);
        $group = $group->load('b2b_store', 'code');
        return $group;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
