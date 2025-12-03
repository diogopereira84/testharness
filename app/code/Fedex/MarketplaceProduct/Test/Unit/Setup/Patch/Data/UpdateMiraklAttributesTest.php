<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Setup\Patch\Data\UpdateMiraklAttributes;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use Fedex\Cms\Api\Cms\SimpleContentReader;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Eav\Setup\EavSetup;

class UpdateMiraklAttributesTest extends TestCase
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
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var SimpleContentReader
     */
    private $contentReader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactory = $this->createMock(EavSetupFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->contentReader = $this->createMock(SimpleContentReader::class);
        $this->eavAttribute = $this->createMock(Attribute::class);
    }

    /**
     * Test getDependencies
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $updateMiraklAttributes = new UpdateMiraklAttributes(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->contentReader,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([], $updateMiraklAttributes->getDependencies());
    }

    /**
     * Test getAliases
     *
     * @return void
     */
    public function testGetAliases()
    {
        $updateMiraklAttributes = new UpdateMiraklAttributes(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->contentReader,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([], $updateMiraklAttributes->getAliases());
    }

    /**
     * Test apply
     *
     * @return void
     */
    public function testApply()
    {
        $updateMiraklAttributes = new UpdateMiraklAttributes(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->contentReader,
            $this->logger,
            $this->eavAttribute
        );

        $this->moduleDataSetup->expects($this->once())
            ->method('getConnection')
            ->willReturnSelf();
        $this->moduleDataSetup->expects($this->once())
            ->method('startSetup');
        $updateMiraklAttributes->apply();
    }

    /**
     * Test updateMiraklAttributes
     *
     * @return void
     */
    public function testUpdateMiraklAttributes()
    {
        $updateMiraklAttributes = new UpdateMiraklAttributes(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->contentReader,
            $this->logger,
            $this->eavAttribute
        );

        $eavSetup = $this->createMock(EavSetup::class);
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->willReturn($eavSetup);
        $this->eavAttribute->expects($this->exactly(count(self::MIRAKL_ATTRIBUTES)))
            ->method('getIdByCode')
            ->willReturn(1);
        $eavSetup->expects($this->exactly(count(self::MIRAKL_ATTRIBUTES)))
            ->method('updateAttribute');
        $updateMiraklAttributes->updateMiraklAttributes();
    }
}
