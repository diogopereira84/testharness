<?php
namespace Fedex\SaaSCommon\Api;

use Magento\Framework\Exception\LocalizedException;

interface CustomerGroupAttributeHandlerInterface
{
    /**
     * Retrieve the attribute ID based on the entity type and attribute code.
     *
     * @param string $entityType
     * @param string $attributeCode
     * @return int|null
     */
    public function getAttributeIdByCode(string $entityType, string $attributeCode): ?int;

    /**
     * Retrieve an array with all customer groups.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllCustomerGroups(): array;

    /**
     * Retrieve an array with all customer groups values.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllCustomerGroupsValues(): array;

    /**
     * Add attribute options based on allowed customer groups options.
     *
     * @param array|null $customerGroupNewOptions
     * @throws \Exception
     * @return void
     */
    public function addAttributeOption(array $customerGroupNewOptions = null): void;

    /**
     * Updates all attribute options for allowed customer groups.
     *
     * @throws \Exception
     * @return void
     */
    public function updateAllAttributeOptions(): void;

    /**
     * Push an entity to the queue for processing.
     *
     * @param int $entityId
     * @param string $entityType
     * @return void
     */
    public function pushEntityToQueue(int $entityId, string $entityType): void;
}


