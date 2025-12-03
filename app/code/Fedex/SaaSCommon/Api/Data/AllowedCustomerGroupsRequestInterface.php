<?php
namespace Fedex\SaaSCommon\Api\Data;

interface AllowedCustomerGroupsRequestInterface
{
    /**
     * Get the entity ID (product or category)
     * @return int
     */
    public function getEntityId();

    /**
     * Set the entity ID
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get the entity type (product|category)
     * @return string
     */
    public function getEntityType();

    /**
     * Set the entity type
     * @param string $entityType
     * @return $this
     */
    public function setEntityType($entityType);
}
