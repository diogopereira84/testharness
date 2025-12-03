<?php

namespace Fedex\LiveSearch\Setup\Patch\Data;

use Fedex\Ondemand\Api\Data\ConfigInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * This file was created to handle the issues cause by UpdateProductCustomizeSearchActionAttributesValuesFixed
 */
class RevertIntAttributesChange implements DataPatchInterface
{
    /**
     * customize_search_action backend type
     * @var string
     */
    private $intBackendType = 'int';
    private $attributeCodeWhere = 'attribute_code = ?';
    private $rowIdWhere = 'row_id IN (?)';
    private $attributeIdWhere = 'attribute_id = ?';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly ConfigInterface $configInterface,
        private readonly ScopeConfigInterface $scopeConfig
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $baseUrl = $this->scopeConfig->getValue('web/secure/base_url');
        //This is to prevent patch from running in PROD since there's no need for this fix to happen there
        if (str_contains($baseUrl, 'staging') || str_contains($baseUrl, 'dev')) {

            /**
             * Get attribute set ID
             */
            $attributeSetId = $this->moduleDataSetup->getConnection()->fetchOne(
                $this->moduleDataSetup->getConnection()->select()
                    ->from(
                        $this->moduleDataSetup->getTable('eav_attribute_set'),
                        ['attribute_set_id']
                    )
                    ->where('attribute_set_name = ?', 'PrintOnDemand')
            );

            /**
             * Get products having attribute set PrintOnDemand
             */
            $printOnDemandProducts = $this->moduleDataSetup->getConnection()->fetchCol(
                $this->moduleDataSetup->getConnection()->select()
                    ->from(
                        $this->moduleDataSetup->getTable('catalog_product_entity'),
                        ['row_id']
                    )
                    ->where('attribute_set_id = ?', $attributeSetId)
            );

            //All below attributes were changed in UpdateProductCustomizeSearchActionAttributeValuesFixed old version
            //a missing WHERE updated all the values under catalog_product_entity_int table for PrintOnDemand products
            $this->updateProductsAttributeSetIdAttribute($attributeSetId, $printOnDemandProducts);
            $this->updateProductsProductAttributeSetsIdAttribute($attributeSetId, $printOnDemandProducts);
            $this->updateProductsSentToCustomerAttribute($printOnDemandProducts);
            $this->updateProductsIsPendingReviewAttribute($printOnDemandProducts);
            $this->updateProductsCustomizableAttribute($printOnDemandProducts);
            $this->updateProductsIsDeliveryOnlyAttribute($printOnDemandProducts);
            $this->updateProductsTaxClassIdAttribute($printOnDemandProducts);
            $this->updateProductsVisibilityAttribute($printOnDemandProducts);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function updateProductsAttributeSetIdAttribute($attributeSetId, $printOnDemandProducts)
    {
        /**
         * Get attribute_set_id attribute details
         */
        $attributeSetIdAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'attribute_set_id')
        );

        /**
         * Set right Attribute Set Value to products from PrintOnDemand Attribute Set.
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => $attributeSetId],
            [
                $this->rowIdWhere => $printOnDemandProducts,
                $this->attributeIdWhere => $attributeSetIdAttribute['attribute_id']
            ]
        );
    }

    public function updateProductsProductAttributeSetsIdAttribute($attributeSetId, $printOnDemandProducts)
    {
        /**
         * Get product_attribute_sets_id attribute details
         */
        $productAttributeSetsIdAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'product_attribute_sets_id')
        );

        /**
         * Set right Product Attribute Sets Id value to products from PrintOnDemand Attribute Set.
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => $attributeSetId],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $productAttributeSetsIdAttribute['attribute_id']
            ]
        );
    }

    public function updateProductsSentToCustomerAttribute($printOnDemandProducts)
    {
        /**
         * Get sent_to_customer attribute details
         */
        $productSentToCustomerAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'sent_to_customer')
        );

        /**
         * Set right Sent To Customer Attribute value to products from PrintOnDemand Attribute Set.
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => 0],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $productSentToCustomerAttribute['attribute_id']
            ]
        );
    }

    public function updateProductsIsPendingReviewAttribute($printOnDemandProducts)
    {
        /**
         * Get is_pending_review attribute details
         */
        $productIsPendingReviewAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'is_pending_review')
        );

        /**
         * Set right Is Pending Review Attribute value to products from PrintOnDemand Attribute Set.
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => 0],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $productIsPendingReviewAttribute['attribute_id']
            ]
        );
    }

    public function updateProductsCustomizableAttribute($printOnDemandProducts)
    {
        /**
         * Get customizable attribute details
         */
        $productCustomizableAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'customizable')
        );

        /**
         * Set right Customizable Attribute value to products from PrintOnDemand Attribute Set.
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => 0],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $productCustomizableAttribute['attribute_id']
            ]
        );
    }

    public function updateProductsIsDeliveryOnlyAttribute($printOnDemandProducts)
    {
        /**
         * Get is_delivery_only attribute details
         */
        $productIsDeliveryOnlyAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'is_delivery_only')
        );

        /**
         * Set right Is Delivery Only Attribute value to products from PrintOnDemand Attribute Set.
         */
        if (isset($productIsDeliveryOnlyAttribute['attribute_id'])) {
            $this->moduleDataSetup->getConnection()->update(
                $this->moduleDataSetup->getTable(
                    'catalog_product_entity_' . $this->intBackendType
                ),
                ['value' => 0],
                [
                    $this->rowIdWhere => $printOnDemandProducts,
                    $this->attributeIdWhere => $productIsDeliveryOnlyAttribute['attribute_id']
                ]
            );
        }

    }

    public function updateProductsTaxClassIdAttribute($printOnDemandProducts)
    {
        /**
         * Get tax_class_id attribute details
         */
        $productTaxClassIdAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'tax_class_id')
        );

        /**
         * Set right Tax Class Id Attribute value to products from PrintOnDemand Attribute Set.
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => 0],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $productTaxClassIdAttribute['attribute_id']
            ]
        );
    }

    public function updateProductsVisibilityAttribute($printOnDemandProducts)
    {
        /**
         * Get visibility attribute details
         */
        $visibilityAttribute = $this->moduleDataSetup->getConnection()->fetchRow(
            $this->moduleDataSetup->getConnection()->select()
                ->from(
                    $this->moduleDataSetup->getTable('eav_attribute'),
                    ['attribute_id', 'backend_type']
                )
                ->where($this->attributeCodeWhere, 'visibility')
        );

        /**
         * Set visibility value 4 for all products in PrintOnDemand attribute set to prevent issue from old version of this file
         */
        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => Visibility::VISIBILITY_BOTH],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $visibilityAttribute['attribute_id'],
                'store_id = ?'          => $this->configInterface->getB2bDefaultStore()
            ]
        );

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable(
                'catalog_product_entity_' . $this->intBackendType
            ),
            ['value' => Visibility::VISIBILITY_NOT_VISIBLE],
            [
                $this->rowIdWhere       => $printOnDemandProducts,
                $this->attributeIdWhere => $visibilityAttribute['attribute_id'],
                'store_id IN (?)'       => [0, 1]
            ]
        );
    }


    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }
}
