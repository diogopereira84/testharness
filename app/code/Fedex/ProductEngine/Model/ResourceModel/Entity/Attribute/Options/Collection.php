<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Model\ResourceModel\Entity\Attribute\Options;

use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as CoreOptionCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * class Collection for preference
 */
class Collection extends CoreOptionCollection
{
    /**
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param ResourceConnection $coreResource
     * @param StoreManagerInterface $storeManager
     * @param ConfigInterface $configInterface
     * @param ToggleConfig $toggleConfig
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        ResourceConnection $coreResource,
        StoreManagerInterface $storeManager,
        protected ConfigInterface $configInterface,
        private ToggleConfig $toggleConfig,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $coreResource,
            $storeManager,
            $connection,
            $resource
        );
    }

    /**
     * Convert collection items to select options array
     *
     * @param string $valueKey
     * @return array
     */
    public function toOptionArray($valueKey = 'value'): array
    {
        if (!$this->toggleConfig->getToggleConfigValue('xmen_remove_adobe_commerce_override')) {
            return $this->_toOptionArray(
                'option_id',
                $valueKey,
                ['choice_id' => 'choice_id', 'option_id' => 'option_id']
            );
        } else {
            return parent::toOptionArray($valueKey);
        }
    }
}
