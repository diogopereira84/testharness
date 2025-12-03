<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Service;

use Fedex\SaaSCommon\Api\CustomerGroupDiffServiceInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Exception\InputException;

/**
 * Model Class CustomerGroupDiffService
 */
class CustomerGroupDiffService implements CustomerGroupDiffServiceInterface
{
    /**
     * Attribute code for allowed customer groups.
     */
    public const ATTRIBUTE_CODE = 'allowed_customer_groups';

    /**
     * Constructor for CustomerGroupDiffService.
     *
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     */
    public function __construct(
        private readonly AttributeOptionManagementInterface $attributeOptionManagement
    ) {}

    /**
     * Retrieve the attribute options for allowed customer groups attribute
     *
     * @return AttributeOptionInterface[]
     * @throws InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function getAllowedCustomerGroupsOptions()
    {
        return $this->attributeOptionManagement->getItems(Product::ENTITY, self::ATTRIBUTE_CODE);
    }

    /**
     * Find missing customer group options in the existing attribute options
     *
     * @param array $existingOptions
     * @return array
     * @throws InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function findMissingCustomerGroupOptions(array $existingOptions): array
    {
        $incoming = $this->convertToLabelValueMap(
            $this->getAllowedCustomerGroupsOptions()
        );

        return array_values(array_diff($existingOptions, $incoming));
    }

    /**
     * Retrieve the allowed customer groups attribute option length.
     *
     * @return int
     * @throws InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function allowedCustomerGroupsAttributeOptionLength(): int
    {
        $options = $this->getAllowedCustomerGroupsOptions();

        if (is_array($options)) {
            return count($options);
        }

        return 0;
    }

    /**
     * Convert an array of AttributeOptionInterface items to a label-value map.
     *
     * @param array $options
     * @return array
     * @throws InputException
     */
    public function convertToLabelValueMap(array $options): array
    {
        $result = [];
        foreach ($options as $optionItem) {
            if(!$optionItem instanceof AttributeOptionInterface) {
                throw new InputException(__('Invalid option item provided.'));
            }

            $result[$optionItem->getValue()] = $optionItem->getValue();
        }
        return $result;
    }
}
