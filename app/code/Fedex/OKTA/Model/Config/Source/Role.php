<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Model\Config\Source;

use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory;

/**
 * Class Role
 *
 * @package Fedex\OKTA\Model\Config\Source
 */
class Role
{
    /**
     * Role constructor
     *
     * @param CollectionFactory $roleCollectionFactory
     */
    public function __construct(
        private CollectionFactory $roleCollectionFactory
    )
    {
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $roles = $this->roleCollectionFactory->create();
        $roles->addFieldToFilter('role_type', \Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE);
        return $roles->toOptionArray();
    }
}
