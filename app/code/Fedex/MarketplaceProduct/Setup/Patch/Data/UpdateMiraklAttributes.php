<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;

class UpdateMiraklAttributes implements DataPatchInterface
{
    private const MIRAKL_ATTRIBUTES = [
        'admin_user_id',
        'allow_message',
        'giftcard_amounts',
        'back_binding_covers',
        'banner_type',
        'binder_spine',
        'bleeds',
        'canva_size',
        'category_ids',
        'collation',
        'cost',
        'created_at',
        'cross_domain_store',
        'cross_domain_url',
        'customizable',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout',
        'custom_layout_update',
        'custom_layout_update_file',
        'country_of_manufacture',
        'cutting',
        'msrp_display_actual_price_type',
        'drilling',
        'price_type',
        'sku_type',
        'weight_type',
        'email_template',
        'status',
        'giftcard_type',
        'gift_message_available',
        'gift_wrapping_available',
        'gift_wrapping_price',
        'external_prod',
        'frame',
        'front_binding_covers',
        'front_back_binding_covers',
        'is_catalog_product',
        'is_redeemable',
        'is_returnable',
        'grommets',
        'has_canva_design',
        'lifetime',
        'links_exist',
        'links_purchased_separately',
        'links_title',
        'imposition',
        'in_html_sitemap',
        'in_xml_sitemap',
        'page_layout',
        'led_light_display',
        'manufacturer',
        'marketing_description',
        'old_id',
        'open_amount_max',
        'open_amount_min',
        'options_container',
        'minimal_price',
        'msrp',
        'has_options',
        'updated_at',
        'url_path',
        'allow_open_amount',
        'quantity_and_stock_status',
        'preset_id',
        'price_view',
        'required_options',
        'print_first_page_on_cover',
        'product_id',
        'readability',
        'related_keywords',
        'related_tgtr_position_behavior',
        'related_tgtr_position_limit',
        'samples_title',
        'news_from_date',
        'news_to_date',
        'shipment_type',
        'shipping_estimator_content',
        'shop_by_type_image',
        'short_description',
        'sign_type',
        'special_price',
        'special_from_date',
        'use_config_allow_message',
        'use_config_email_template',
        'use_config_is_redeemable',
        'use_config_lifetime',
        'use_in_crosslinking',
        'special_to_date',
        'shipping_estimator_content_alert',
        'swatch_image',
        'tabs',
        'tax_class_id',
        'tier_price',
        'upsell_tgtr_position_behavior',
        'upsell_tgtr_position_limit',
        'visibility',
        'visible_attributes',
        'weight'
    ];

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     * @param SimpleContentReader $contentReader
     * @param LoggerInterface $logger
     * @param Attribute $eavAttribute
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private EavSetupFactory          $eavSetupFactory,
        private SimpleContentReader      $contentReader,
        private LoggerInterface          $logger,
        private Attribute                $eavAttribute
    )
    {
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->updateMiraklAttributes();
    }

    /**
     * Update Mirakl Attributes
     *
     * @return void
     */
    public function updateMiraklAttributes()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        foreach (self::MIRAKL_ATTRIBUTES as $attributeCode) {
            try {
                $attrProduct = $this->eavAttribute->getIdByCode(Product::ENTITY, $attributeCode);
                if (!empty($attrProduct)) {
                    $eavSetup->updateAttribute(
                        \Magento\Catalog\Model\Product::ENTITY,
                        $attributeCode,
                        [
                            'mirakl_is_exportable' => false
                        ]
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return $this->getDependencies();
    }
}
