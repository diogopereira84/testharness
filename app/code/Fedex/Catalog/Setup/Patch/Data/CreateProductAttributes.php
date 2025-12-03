<?php
declare(strict_types=1);
namespace Fedex\Catalog\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * @codeCoverageIgnore
 */
class CreateProductAttributes implements DataPatchInterface
{
    /**
     * CreateCategoryAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory $eavSetupFactory,
        private SimpleContentReader $contentReader,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Apply patch
     *
     * @return DataPatchInterface|void
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->removeAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'shipping_estimator_content_block'
        );
        $eavSetup->removeAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'shipping_estimator_content'
        );
        $eavSetup->removeAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'shipping_estimator_content_alert'
        );

        $attributes = [
            'shipping_estimator_content' =>
                [
                    'type' => 'text',
                    'label' => 'Shipping Estimate',
                    'input' => 'textarea',
                    'sort_order' => 15,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Content',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => $this->contentReader->getContent('attribute_shipping_estimator.html'),
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'is_html_allowed_on_front' => true,
                    'is_wysiwyg_enabled' => true,
                    'unique' => false
                ],
            'shipping_estimator_content_alert' =>
                [
                    'type' => 'text',
                    'label' => 'Specific Product Information',
                    'input' => 'textarea',
                    'sort_order' => 15,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'group' => 'Content',
                    'visible' => true,
                    'required' => false,
                    'user_defined' => false,
                    'default' => $this->contentReader->getContent('attribute_shipping_estimator_alert.html'),
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'used_in_product_listing' => false,
                    'is_html_allowed_on_front' => true,
                    'is_wysiwyg_enabled' => true,
                    'unique' => false
                ]
        ];

        foreach ($attributes as $attributeCode => $attribute) {
            try {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeCode,
                    $attribute
                );
                $eavSetup->updateAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    $attributeCode,
                    [
                        'is_wysiwyg_enabled' => 1,
                        'is_pagebuilder_enabled' => 1
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }
}
