<?php
declare(strict_types=1);

namespace Fedex\LiveSearch\Plugin\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Product\AttributeMetadata;

class AttributeMetadataPlugin
{

    /**
     * Plugin to remove default-$number value from within products value list
     *
     * @param AttributeMetadata $subject
     * @param array $result
     * @return array
     */
    public function afterGetAttributeValue(AttributeMetadata $subject, array $result): array //NOSONAR
    {
        if (!empty($result)) {
            $defaultOptionFound = array_filter($result, function ($option) {
                return str_contains($option, 'default-');
            });
            if (!empty($defaultOptionFound)) {
                $result = array_diff($result, $defaultOptionFound);
            }
        }
        return $result;
    }
}
