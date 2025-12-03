<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Ui\Component\Form\ParentGroup;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Exception\StateException;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;

/**
 * Options tree for "ParentGroup" field
 */
class Options extends AbstractSource
{
    /**
     * @param GroupCollectionFactory $groupCollectionFactory
     */
    public function __construct(
        protected GroupCollectionFactory $groupCollectionFactory
    )
    {
    }

    /**
     * Retrieve all customer group parent group as an options array.
     *
     * @return array
     * @throws StateException
     */
    public function getAllOptions()
    {
        if (empty($this->_options)) {
            $options = [];
            $customerGroups = $this->groupCollectionFactory->create();
            foreach ($customerGroups->getItems() as $customerGroup) {
                $options[] = [
                    'value' => $customerGroup->getCustomerGroupId(),
                    'label' => $customerGroup->getCustomerGroupCode(),
                ];
            }
            $this->_options = $options;
        }
        return $this->_options;
    }
}
