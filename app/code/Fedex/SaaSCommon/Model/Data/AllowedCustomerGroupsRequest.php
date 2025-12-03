<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Data;

use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Magento\Framework\DataObject;

class AllowedCustomerGroupsRequest extends DataObject implements AllowedCustomerGroupsRequestInterface
{
    public function getEntityId()
    {
        return $this->getData('entity_id');
    }

    public function setEntityId($entityId)
    {
        return $this->setData('entity_id', $entityId);
    }

    public function getEntityType()
    {
        return $this->getData('entity_type');
    }

    public function setEntityType($entityType)
    {
        return $this->setData('entity_type', $entityType);
    }
}
