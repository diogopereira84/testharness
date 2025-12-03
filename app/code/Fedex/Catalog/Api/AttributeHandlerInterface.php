<?php
namespace Fedex\Catalog\Api;

interface AttributeHandlerInterface
{
    /**
     * Retrieve attribute options for a given attribute ID.
     *
     * @param string $attributeId
     * @return array
     */
    public function getAttributeOptions(string $attributeId): array;

    /**
     * Retrieve attribute ID by entity type and attribute code.
     *
     * @param string $entityType
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeIdByCode(string $entityType, string $attributeCode): string;

    /**
     * Retrieve all shared catalog options.
     *
     * @return array
     */
    public function getAllSharedCatalogOptions(): array;

    /**
     * Retrieve new shared catalog options that are not yet added to the attribute.
     *
     * @param string $attributeId
     * @return array
     */
    public function getNewSharedCatalogOptions(string $attributeId): array;

    /**
     * Add attribute options based on shared catalog options.
     *
     * @param array|null $sharedCatalogNewOptions
     * @return void
     */
    public function addAttributeOption(array $sharedCatalogNewOptions = null): void;

    /**
     * Get the number of attribute options for the shared catalog attribute.
     *
     * @param string $attributeId
     * @return int
     */
    public function sharedCatalogAttributeOptionLength(string $attributeId): int;
}
