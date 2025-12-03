<?php
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Eav\Model\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class UpdateProductPreviewImageForAttributes implements DataPatchInterface
{
    /**
     * @param Config $eavConfig
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly Config $eavConfig,
        private readonly LoggerInterface $logger,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Apply patch
     */
    public function apply()
    {
        try {
            $attributesToUpdate = ['color', 'ruling', 'qty_Sets'];
            foreach ($attributesToUpdate as $attributeCode) {
                $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);

                if ($attribute) {
                    $additionalData = $attribute->getAdditionalData();
                    $additionalDataArary = $this->serializer->unserialize($additionalData);
                    foreach($additionalDataArary as $key=>$value){
                        $additionalDataArary[$key] = $value;
                        if($key == 'update_product_preview_image'){
                            $additionalDataArary[$key] = 1;
                        }
                    }
                    $additionalDataSerializeArary = $this->serializer->serialize($additionalDataArary);
                    $attribute->setData('additional_data',$additionalDataSerializeArary);
                    $attribute->save();
                 } else {
                    $this->logger->warning("Attribute not found: " . $attributeCode);
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical('Error updating product preview image attribute: ' . $e->getMessage());
        }
    }

    /**
     * Get aliases (optional)
     *
     * @return string[]
     */
    public function getAliases()
    {
        return [];
    }
    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
