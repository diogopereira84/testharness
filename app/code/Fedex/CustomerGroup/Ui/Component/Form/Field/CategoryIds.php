<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Ui\Component\Form\Field;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Form\Field;

/**
 * Class CategoryIds.
 */
class CategoryIds extends Field
{
    /**
     * Field config key.
     */
    const FIELD_CONFIG_KEY = 'config';
    /**
     * Table Name.
     */
    const TABLE_NAME = 'parent_customer_group';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param Registry $coreRegistry
     * @param ResourceConnection $resourceConnection
     * @param array|UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private GroupRepositoryInterface $groupRepository,
        protected Registry $coreRegistry,
        protected ResourceConnection $resourceConnection,
        array $components,
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $this->setData(
            self::FIELD_CONFIG_KEY,
            array_replace_recursive(
                (array) $this->getData(self::FIELD_CONFIG_KEY),
                (array) $this->getConfigDefaultData()
            )
        );

        parent::prepare();
    }

    /**
     * Get field config default data.
     *
     * @return array
     */
    protected function getConfigDefaultData()
    {
        $groupId = $this->coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        $categoryIds = $this->getCategoryIds($groupId);
        if ($categoryIds) {
            return [
                'value' => $categoryIds
            ];
        } else {
            return [
                'value' => []
            ];
        }
    }


    /**
     * Get Saved Category Ids
     *
     * @param string $customerGroupId
     * @return array
     */
    public function getCategoryIds($customerGroupId)
    {
        $categoryIds = [];
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::TABLE_NAME);

        $select = $connection->select()
                    ->from($tableName, ['category_ids'])
                    ->where('customer_group_id = ?', $customerGroupId);

        $categoryIds = $connection->fetchOne($select);

        if ($categoryIds !== null && $categoryIds!= '') {
            $categoryIds = explode(',', $categoryIds);
        }

        return $categoryIds;
    }
}
