<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Api;

use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Exception\InputException;

interface CustomerGroupDiffServiceInterface
{
    /**
     * Retrieve the attribute options for allowed customer groups attribute
     *
     * @return AttributeOptionInterface[]
     * @throws InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getAllowedCustomerGroupsOptions();

    /**
     * Find missing customer group options in the existing attribute options
     *
     * @param array $existingOptions
     * @return array
     * @throws InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function findMissingCustomerGroupOptions(array $existingOptions): array;

    /**
     * Retrieve the allowed customer groups attribute option length.
     *
     * @return int
     * @throws InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function allowedCustomerGroupsAttributeOptionLength(): int;

    /**
     * Convert an array of AttributeOptionInterface items to a label-value map.
     *
     * @param array $options
     * @return array
     * @throws InputException
     */
    public function convertToLabelValueMap(array $options): array;
}

