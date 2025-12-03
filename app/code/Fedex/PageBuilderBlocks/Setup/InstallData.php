<?php

namespace Fedex\PageBuilderBlocks\Setup;

use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\PageBuilder\Model\TemplateFactory;

class InstallData implements InstallDataInterface
{
    public const PRODUCT_PAGE_TEMPLATE_FAQ= 'Product Page Template FAQ';
    /**
     * Pub static path
     *
     * @var $staticPath
     */
    protected $staticPath = "../static/adminhtml/Magento/backend/en_US/Fedex_PageBuilderBlocks/images";
   
    /**
     * InstallData constructor
     *
     * @param BlockFactory $blockFactory
     * @param TemplateFactory $templateFactory
     * @param PageFactory $pageFactory
     */
    public function __construct(
        private BlockFactory $blockFactory,
        private TemplateFactory $templateFactory,
        private PageFactory $pageFactory
    )
    {
    }

    /**
     * Install method
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $blocks = $this->getBlocks();

        $this->deleteBlockByIdentifier('hero-banner-product-template');
        $heroBanner = $this->blockFactory->create();
        $heroBanner->addData($blocks['hero-banner-product-template'])->save();

        $this->deleteBlockByIdentifier('shop-by-type');
        $shopByType = $this->blockFactory->create();
        $shopByType->addData($blocks['shop-by-type'])->save();

        $this->deleteBlockByIdentifier('product-pricing');
        $productPricing = $this->blockFactory->create();
        $productPricing->addData($blocks['product-pricing'])->save();

        $this->deleteBlockByIdentifier('g7-master-printer-certification');
        $g7Master = $this->blockFactory->create();
        $g7Master->addData($blocks['g7-master-printer-certification'])->save();

        $this->deleteBlockByIdentifier(self::PRODUCT_PAGE_TEMPLATE_FAQ);
        $productPageTemplateFaq = $this->blockFactory->create();
        $productPageTemplateFaq->addData($blocks[self::PRODUCT_PAGE_TEMPLATE_FAQ])->save();

        $this->deleteOldProductTemplatePage();
        $productTemplatePageData = $this->getProductTemplatePage(
            $heroBanner->getId(),
            $shopByType->getId(),
            $productPricing->getId(),
            $g7Master->getId(),
            $productPageTemplateFaq->getId()
        );
        $pageTemplateModel = $this->pageFactory->create();
        $pageTemplateModel->addData($productTemplatePageData)->save();

        $productTemplatePageBuilderTemplateData = $this->getProductTemplatePageBuilderTemplate(
            $heroBanner->getId(),
            $shopByType->getId(),
            $productPricing->getId(),
            $g7Master->getId(),
            $productPageTemplateFaq->getId()
        );
        $productTemplatePageBuilderTemplateModel = $this->templateFactory->create();
        $productTemplatePageBuilderTemplateModel->addData($productTemplatePageBuilderTemplateData)->save();

        $installer->endSetup();
    }

    /**
     * GetBlock method
     */
    private function getBlocks()
    {
        $blocks = [];

        $blocks = $this->getHeroBannerTemplateBlock($blocks);

        $blocks = $this->getShopByTypeBlock($blocks);

        $blocks = $this->getProductPricingBlock($blocks);

        $blocks = $this->getProductTemplateFaqBlock($blocks);

        $blocks = $this->getG7MasterBlock($blocks);

        return $blocks;
    }

    /**
     * GetHeroBanner Block method
     *
     * @return array
     */
    public function getHeroBannerTemplateBlock($blocks)
    {
        $blocks['hero-banner-product-template'] = [
            "title" => "Hero Banner Product Template",
            "identifier" => "hero-banner-product-template",
            "content" => '<div class="hero-banner product-template-banner"
                          data-content-type="row" data-appearance="full-bleed" data-enable-parallax="0"
                          data-parallax-speed="0.5" data-background-images="{}" data-background-type="image"
                          data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true"
                          data-video-fallback-src="" data-element="main" style="justify-content: flex-start;
                          display: flex; flex-direction: column; background-position: left top;
                          background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                          border-style: none; border-width: 1px; border-radius: 0px;">
                          <div class="canva-modal-link" data-content-type="banner"
                          data-appearance="collage-left" data-show-button="always" data-show-overlay="always"
                          data-element="main" style="margin: 0px;"><a href="https://www.fedex.com" target="_blank"
                          data-link-type="default" data-element="link">
                          <div class="pagebuilder-banner-wrapper"
                          data-background-images="{\&quot;desktop_image\&quot;:\&quot;
                            {{media url=wysiwyg/PrdTmpltDesktpBanner.png}}\&quot;,\&quot;mobile_image\&quot;:\&quot;
                            {{media url=wysiwyg/PrdTmpltMobBanner.png}}\&quot;,
                            \&quot;desktop_medium_image\&quot;:\&quot;
                            {{media url=wysiwyg/PrdTmpltLapBanner.png}}\&quot;,\&quot;mobile_medium_image\&quot;:\&quot;
                            {{media url=wysiwyg/PrdTmpltTabBanner.png}}\&quot;}" data-background-type="image"
                            data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true"
                            data-video-fallback-src="" data-element="wrapper" style="background-position: left top;
                            background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                            border-style: none; border-width: 1px;
                            border-radius: 0px; padding: 0px; min-height: 250px;">
                            <div class="pagebuilder-overlay" data-overlay-color="" data-element="overlay"
                            style="background-color: transparent;"><div class="pagebuilder-collage-content">
                            <div class="message" messagelaptop="" messagetablet="" messagemobile="<h1>
                            <span id=&quot;XWVOV98&quot; style=&quot;color: #000000;&quot;>You make the</span></h1>
                          <h1><span id=&quot;XWVOV98&quot; style=&quot;color: #000000;&quot;>memories. </span>
                          <span id=&quot;XWVOV98&quot; style=&quot;color: #000000;&quot;>We\'ll</span></h1>
                          <h1><span id=&quot;XWVOV98&quot; style=&quot;color: #000000;&quot;>print them.<br>
                          </span></h1>" data-element="content"><h1>
                          <span style="color: #ffffff;">Create your own yard sign</span>
                          </h1></div><div class="largetscreenmessage" style="display:none;">
                          <h1><span style="color: #ffffff;">Create your own yard sign</span>
                          </h1></div><button type="button" class="pagebuilder-banner-button pagebuilder-button-primary"
                          data-element="button" style="opacity: 1; visibility: visible;">BROWSE TEMPLATES</button>
                          </div></div></div></a></div></div>',
            "is_active" => 1,
            "stores" => [0]
        ];

        return $blocks;
    }

    /**
     * GetShopByType Block method
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getShopByTypeBlock($blocks)
    {
        $blocks['shop-by-type'] = [
            "title" => "Shop by Type",
            "identifier" => "shop-by-type",
            "content" => "<div data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\">
                        <div class=\"shop-by-type\" data-enable-parallax=\"0\"
                        data-parallax-speed=\"0.5\" data-background-images=\"{}\"
                        data-background-type=\"image\" data-video-loop=\"true\"
                        data-video-play-only-visible=\"true\"
                        data-video-lazy-load=\"true\" data-video-fallback-src=\"\"
                        data-element=\"inner\" style=\"justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px 0px 10px; padding: 10px;\">
                        <h2 class=\"h2-title\" data-content-type=\"heading\"
                        data-appearance=\"default\" data-element=\"main\"
                        style=\"border-style: none; border-width: 1px; border-radius: 0px;\">
                        Shop by Type</h2>
                        <div class=\"pagebuilder-column-group\" style=\"display: flex;\"
                        data-content-type=\"column-group\" data-grid-size=\"12\"
                        data-element=\"main\"><div class=\"pagebuilder-column tile-view\"
                        data-content-type=\"column\" data-appearance=\"full-height\"
                        data-background-images=\"{}\" data-element=\"main\" style=\"justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: solid;
                        border-color: rgb(216, 216, 216); border-width: 1px;
                        border-radius: 0px; width: 33.3333%; align-self: stretch;\">
                        <div data-content-type=\"products\" data-appearance=\"simple-product-grid\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        {{widget type=\"Magento\CatalogWidget\Block\Product\ProductsList\"
                        template=\"Fedex_PageBuilderExtensionProducts::product/
                        widget/content/simple-product-grid.phtml\"
                        anchor_text=\"\" id_path=\"\" show_pager=\"0\" products_count=\"5\"
                        condition_option=\"sku\" condition_option_value=\"1614105200640-4\"
                        type_name=\"Catalog Products List\" product_image_attribute=\"shop_by_type_grid\"
                        conditions_encoded=\"^[`1`:^[`aggregator`:`all`,`new_child`:``,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,
                        `value`:`1`^],`1--1`:^[`operator`:`()`,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,
                        `attribute`:`sku`,`value`:`1614105200640-4`^]^]\" sort_order=\"position_by_sku\"}}
                        </div><div class=\"bullets\" data-content-type=\"text\"
                        data-appearance=\"default\" data-element=\"main\"style=\"border-style: none;
                        border-width: 1px; border-radius: 0px; smargin: 0px; padding: 0px;\"><ul>
                        <li>Corrugated plastic sign with wire H-stake display</li>
                        <li>Environmental factors are not a concern</li></ul></div>
                        <div data-content-type=\"buttons\" data-appearance=\"inline\" data-same-width=\"false\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding-bottom: 0px;\">
                        <div class=\"fedex-bold\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\" style=\"display: inline-block;\">
                        <a class=\"pagebuilder-button-link\"
                        href=\"{{widget type='Magento\Catalog\Block\Product\Widget\Link' id_path='product/578'
                        template='Magento_PageBuilder::widget/link_href.phtml'
                        type_name='Catalog Product Link' }}\"
                        target=\"\" data-link-type=\"product\"
                        data-element=\"link\" style=\"text-align: center;\">
                        <span data-element=\"link_text\">SHOP NOW</span></a></div></div></div>
                        <div class=\"pagebuilder-column tile-view\" data-content-type=\"column\"
                        data-appearance=\"full-height\" data-background-images=\"{}\"
                        data-element=\"main\" style=\"justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll;
                        border-style: solid; border-color: rgb(216, 216, 216);
                        border-width: 1px; border-radius: 0px; width: 33.3333%; align-self: stretch;\">
                        <div data-content-type=\"products\" data-appearance=\"simple-product-grid\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        {{widget type=\"Magento\CatalogWidget\Block\Product\ProductsList\"
                        template=\"Fedex_PageBuilderExtensionProducts::product/widget/
                        content/simple-product-grid.phtml\" anchor_text=\"\" id_path=\"\" show_pager=\"0\"
                        products_count=\"5\" condition_option=\"sku\" condition_option_value=\"1593103993699-4\"
                        type_name=\"Catalog Products List\" product_image_attribute=\"shop_by_type_grid\"
                        conditions_encoded=\"^[`1`:^[`aggregator`:`all`,`new_child`:``,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,
                        `value`:`1`^],`1--1`:^[`operator`:`()`,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,
                        `attribute`:`sku`,`value`:`1593103993699-4`^]^]\" sort_order=\"position_by_sku\"}}</div>
                        <div class=\"bullets\" data-content-type=\"text\"
                        data-appearance=\"default\" data-element=\"main\"
                        style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\"><ul>
                        <li>Corrugated plastic sign with metal frame</li>
                        <li>Stand is more durable than short-term</li>
                        <li>Better withstands wind and traffic conditions</li>
                        <li>For outdoor use</li><li>Rust resistant</li>
                        </ul></div><div data-content-type=\"buttons\"
                        data-appearance=\"inline\" data-same-width=\"false\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        <div class=\"fedex-bold\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\"
                        style=\"display: inline-block;\"><a class=\"pagebuilder-button-link\"
                        href=\"{{widget type='Magento\Catalog\Block\Product\Widget\Link'
                        id_path='product/72' template='Magento_PageBuilder::widget/link_href.phtml'
                        type_name='Catalog Product Link' }}\" target=\"\"
                        data-link-type=\"product\" data-element=\"link\"
                        style=\"text-align: center;\"><span data-element=\"link_text\">SHOP NOW</span>
                        </a></div></div></div><div class=\"pagebuilder-column tile-view\"
                        data-content-type=\"column\" data-appearance=\"full-height\"
                        data-background-images=\"{}\" data-element=\"main\"
                        style=\"justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: solid; border-color: rgb(216, 216, 216);
                        border-width: 1px; border-radius: 0px; width: 33.3333%; align-self: stretch;\">
                        <div data-content-type=\"products\" data-appearance=\"simple-product-grid\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        {{widget type=\"Magento\CatalogWidget\Block\Product\ProductsList\"
                        template=\"Fedex_PageBuilderExtensionProducts::product/widget/content/
                        simple-product-grid.phtml\" anchor_text=\"\" id_path=\"\" show_pager=\"0\"
                        products_count=\"5\" condition_option=\"sku\" condition_option_value=\"1594830761054-4\"
                        type_name=\"Catalog Products List\" product_image_attribute=\"shop_by_type_grid\"
                        conditions_encoded=\"^[`1`:^[`aggregator`:`all`,`new_child`:``,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,
                        `value`:`1`^],`1--1`:^[`operator`:`()`,`type`:
                        `Magento||CatalogWidget||Model||Rule||Condition||Product`,
                        `attribute`:`sku`,`value`:`1594830761054-4`^]^]\" sort_order=\"position_by_sku\"}}</div>
                        <div class=\"bullets\" data-content-type=\"text\" data-appearance=\"default\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\"><ul><li>Metal sign with metal frame</li>
                        <li>Weather and rust resistant</li><li>Recyclable</li></ul></div>
                        <div data-content-type=\"buttons\" data-appearance=\"inline\"
                        data-same-width=\"false\" data-element=\"main\"
                        style=\"border-style: none; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 0px;\">
                        <div class=\"fedex-bold\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\" style=\"display: inline-block;\">
                        <a class=\"pagebuilder-button-link\"
                        href=\"{{widget type='Magento\Catalog\Block\Product\Widget\Link'
                        id_path='product/530' template='Magento_PageBuilder::widget/link_href.phtml'
                        type_name='Catalog Product Link' }}\" target=\"\"
                        data-link-type=\"product\" data-element=\"link\" style=\"text-align: center;\">
                        <span data-element=\"link_text\">SHOP NOW</span></a></div></div></div></div>
                        <div class=\"pagebuilder-column-group\" style=\"display: flex;\"
                        data-content-type=\"column-group\" data-grid-size=\"12\" data-element=\"main\">
                        <div class=\"pagebuilder-column tile-view\" data-content-type=\"column\"
                        data-appearance=\"full-height\" data-background-images=\"{}\"
                        data-element=\"main\" style=\"justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: solid;
                        border-color: rgb(216, 216, 216); border-width: 1px; border-radius: 0px;
                        width: 33.3333%; align-self: stretch;\">
                        <div data-content-type=\"products\" data-appearance=\"simple-product-grid\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        {{widget type=\"Magento\CatalogWidget\Block\Product\ProductsList\"
                        template=\"Fedex_PageBuilderExtensionProducts::product/widget/content/
                        simple-product-grid.phtml\" anchor_text=\"\" id_path=\"\" show_pager=\"0\" products_count=\"5\"
                        condition_option=\"sku\" condition_option_value=\"1614105200640-4\"
                        type_name=\"Catalog Products List\" product_image_attribute=\"shop_by_type_grid\"
                        conditions_encoded=\"^[`1`:^[`aggregator`:`all`,`new_child`:``,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,
                        `value`:`1`^],`1--1`:^[`operator`:`()`,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,
                        `attribute`:`sku`,`value`:`1614105200640-4`^]^]\"
                        sort_order=\"position_by_sku\"}}</div>
                        <div class=\"bullets\" data-content-type=\"text\"
                        data-appearance=\"default\" data-element=\"main\"
                        style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\"><ul>
                        <li>Corrugated plastic sign with wire H-stake display</li>
                        <li>Environmental factors are not a concern</li></ul></div>
                        <div data-content-type=\"buttons\" data-appearance=\"inline\"
                        data-same-width=\"false\" data-element=\"main\"
                        style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding-bottom: 0px;\">
                        <div class=\"fedex-bold\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\" style=\"display: inline-block;\">
                        <a class=\"pagebuilder-button-link\"
                        href=\"{{widget type='Magento\Catalog\Block\Product\Widget\Link'
                        id_path='product/578' template='Magento_PageBuilder::widget/link_href.phtml'
                        type_name='Catalog Product Link' }}\" target=\"\"
                        data-link-type=\"product\" data-element=\"link\" style=\"text-align: center;\">
                        <span data-element=\"link_text\">SHOP NOW</span></a></div></div></div>
                        <div class=\"pagebuilder-column tile-view\"
                        data-content-type=\"column\" data-appearance=\"full-height\"
                        data-background-images=\"{}\" data-element=\"main\" style=\"justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: solid; border-color: rgb(216, 216, 216);
                        border-width: 1px; border-radius: 0px; width: 33.3333%; align-self: stretch;\">
                        <div data-content-type=\"products\" data-appearance=\"simple-product-grid\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        {{widget type=\"Magento\CatalogWidget\Block\Product\ProductsList\"
                        template=\"Fedex_PageBuilderExtensionProducts::product/
                        widget/content/simple-product-grid.phtml\"
                        anchor_text=\"\" id_path=\"\" show_pager=\"0\" products_count=\"5\"
                        condition_option=\"sku\" condition_option_value=\"1593103993699-4\"
                        type_name=\"Catalog Products List\" product_image_attribute=\"shop_by_type_grid\"
                        conditions_encoded=\"^[`1`:^[`aggregator`:`all`,`new_child`:``,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,
                        `value`:`1`^],`1--1`:^[`operator`:`()`,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,
                        `attribute`:`sku`,`value`:`1593103993699-4`^]^]\"
                        sort_order=\"position_by_sku\"}}</div>
                        <div class=\"bullets\" data-content-type=\"text\"
                        data-appearance=\"default\" data-element=\"main\"
                        style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\"><ul>
                        <li>Corrugated plastic sign with metal frame</li>
                        <li>Stand is more durable than short-term</li>
                        <li>Better withstands wind and traffic conditions</li>
                        <li>For outdoor use</li>
                        <li>Rust resistant</li>
                        </ul></div>
                        <div data-content-type=\"buttons\" data-appearance=\"inline\"
                        data-same-width=\"false\" data-element=\"main\" style=\"border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;\">
                        <div class=\"fedex-bold\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\"
                        style=\"display: inline-block;\">
                        <a class=\"pagebuilder-button-link\"
                        href=\"{{widget type='Magento\Catalog\Block\Product\Widget\Link'
                        id_path='product/72' template='Magento_PageBuilder::widget/link_href.phtml'
                        type_name='Catalog Product Link' }}\" target=\"\"
                        data-link-type=\"product\" data-element=\"link\" style=\"text-align: center;\">
                        <span data-element=\"link_text\">SHOP NOW</span>
                        </a></div></div></div>
                        <div class=\"pagebuilder-column tile-view\" data-content-type=\"column\"
                        data-appearance=\"full-height\" data-background-images=\"{}\"
                        data-element=\"main\" style=\"justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: solid;
                        border-color: rgb(216, 216, 216); border-width: 1px;
                        border-radius: 0px; width: 33.3333%; align-self: stretch;\">
                        <div data-content-type=\"products\" data-appearance=\"simple-product-grid\"
                        data-element=\"main\" style=\"border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px;
                        padding: 0px;\">{{widget type=\"Magento\CatalogWidget\Block\Product\ProductsList\"
                        template=\"Fedex_PageBuilderExtensionProducts::product/
                        widget/content/simple-product-grid.phtml\"
                        anchor_text=\"\" id_path=\"\" show_pager=\"0\" products_count=\"5\"
                        condition_option=\"sku\" condition_option_value=\"1594830761054-4\"
                        type_name=\"Catalog Products List\" product_image_attribute=\"shop_by_type_grid\"
                        conditions_encoded=\"^[`1`:^[`aggregator`:`all`,`new_child`:``,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,
                        `value`:`1`^],`1--1`:^[`operator`:`()`,
                        `type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,
                        `attribute`:`sku`,`value`:`1594830761054-4`^]^]\"
                        sort_order=\"position_by_sku\"}}</div>
                        <div class=\"bullets\" data-content-type=\"text\"
                        data-appearance=\"default\" data-element=\"main\" style=\"border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;\"><ul>
                        <li>Metal sign with metal frame</li>
                        <li>Weather and rust resistant</li>
                        <li>Recyclable</li>
                        </ul></div>
                        <div data-content-type=\"buttons\" data-appearance=\"inline\"
                        data-same-width=\"false\" data-element=\"main\" style=\"border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;\">
                        <div class=\"fedex-bold\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\" style=\"display: inline-block;\">
                        <a class=\"pagebuilder-button-link\"
                        href=\"{{widget type='Magento\Catalog\Block\Product\Widget\Link'
                        id_path='product/530' template='Magento_PageBuilder::widget/link_href.phtml'
                        type_name='Catalog Product Link' }}\"
                        target=\"\" data-link-type=\"product\" data-element=\"link\" style=\"text-align: center;\">
                        <span data-element=\"link_text\">SHOP NOW</span></a></div></div></div></div>
                        <div data-content-type=\"buttons\" data-appearance=\"inline\"
                        data-same-width=\"false\" data-element=\"main\"
                        style=\"text-align: center; border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">
                        <div class=\"show-more hide\" data-content-type=\"button-item\"
                        data-appearance=\"default\" data-element=\"main\" style=\"display: inline-block;\">
                        <a class=\"pagebuilder-button-secondary\" href=\"#\"
                        target=\"\" data-link-type=\"default\"
                        data-element=\"link\" style=\"text-align: center;\">
                        <span data-element=\"link_text\">SHOW MORE PRODUCTS</span>
                        </a></div></div>
                        <div data-content-type=\"html\" data-appearance=\"default\"
                        data-element=\"main\" style=\"border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;\">&lt;
                        script type=\"text/x-magento-init\"&gt;
                        {
                            \".shop-by-type\": {
                                \"Magento_PageBuilder/js/components/shop-by-block\": {}
                            }
                        }
                        &lt;/script&gt;</div></div></div>",
            "is_active" => 1,
             "stores" => [0]
        ];

        return $blocks;
    }

    /**
     * GetProductPricing Block method
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getProductPricingBlock($blocks)
    {
        $blocks['product-pricing'] = [
            "title" => "Product Pricing",
            "identifier" => "product-pricing",
            "content"   => '<div data-content-type="row" data-appearance="contained" data-element="main">
                            <div class="product-pricing-section-block hide"
                            data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}"
                            data-background-type="image" data-video-loop="true"
                            data-video-play-only-visible="true" data-video-lazy-load="true"
                            data-video-fallback-src="" data-element="inner"
                            style="justify-content: flex-start; display: flex;
                            flex-direction: column; background-position: left top;
                            background-size: cover; background-repeat: no-repeat;
                            background-attachment: scroll; border-style: none; border-width: 1px;
                            border-radius: 0px; margin: 0px 0px 10px; padding: 10px;">
                            <h2 class="h2-title" data-content-type="heading"
                            data-appearance="default" data-element="main"
                            style="border-style: none; border-width: 1px; border-radius: 0px;">
                            Product Pricing Section</h2>
                            <div class="tab-align-left" data-content-type="tabs"
                            data-appearance="default" data-active-tab="" data-element="main"
                            style="margin: 0px; padding: 0px;">
                            <ul role="tablist" class="tabs-navigation" data-element="navigation"
                            style="text-align: left;">
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#CHGJL8U" class="tab-title">
                            <span class="tab-title">Custom Boxes</span></a></li>
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#KEE6L6Y" class="tab-title">
                            <span class="tab-title">Indoor Banners</span></a></li>
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#JX9GY0G" class="tab-title">
                            <span class="tab-title">Styrene Signs</span></a></li>
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#O1CFHX9" class="tab-title">
                            <span class="tab-title">Announcements</span></a></li>
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#O61P56X" class="tab-title">
                            <span class="tab-title">Mounted Posters</span></a></li>
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#Y8F22BE" class="tab-title">
                            <span class="tab-title">Presentations</span></a></li>
                            <li role="tab" class="tab-header" data-element="headers"
                            style="border-radius: 0px; border-width: 1px;">
                            <a href="#SALUNFE" class="tab-title">
                            <span class="tab-title">Rubber Stamps</span></a></li></ul>
                            <div class="tabs-content" data-element="content"
                            style="border-width: 1px; border-radius: 0px; min-height: 300px;">
                            <div data-content-type="tab-item" data-appearance="default"
                            data-tab-name="Custom Boxes" data-background-images="{}"
                            data-element="main" id="CHGJL8U" style="justify-content: flex-start;
                            display: flex; flex-direction: column; background-position: left top;
                            background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                            border-width: 1px; border-radius: 0px; margin: 0px; padding: 40px;">
                            <div class="pagebuilder-column-group" style="display: flex;"
                            data-content-type="column-group" data-grid-size="12" data-element="main">
                            <div class="pagebuilder-column left-column-image" data-content-type="column"
                            data-appearance="full-height" data-background-images="{}"
                            data-element="main" style="justify-content: flex-start; display: flex;
                            flex-direction: column; background-position: center center;
                            background-size: cover; background-repeat: no-repeat;
                            background-attachment: scroll; text-align: center; border-style: none;
                            border-width: 1px; border-radius: 0px; width: 50%; margin: 0px; padding: 10px;
                            align-self: stretch;">
                            <div data-content-type="banner" data-appearance="poster"
                            data-show-button="never" data-show-overlay="never"
                            data-element="main" style="margin: 0px;">
                            <div data-element="empty_link">
                            <div class="pagebuilder-banner-wrapper"
                            data-background-images="{\&quot;desktop_image\&quot;:\&quot;
                            {{media url=wysiwyg/cq5dam-dsk-300x400.jpeg}}\&quot;,\&quot;mobile_image\&quot;:\&quot;
                            {{media url=wysiwyg/cq5dam-mob-120x160.jpeg}}\&quot;,
                            \&quot;desktop_medium_image\&quot;:\&quot;
                            {{media url=wysiwyg/cq5dam-dsk-300x400_1.jpeg}}\&quot;,
                            \&quot;mobile_medium_image\&quot;:\&quot;
                            {{media url=wysiwyg/cq5dam-tablet-150x200.jpeg}}\&quot;}"
                            data-background-type="image" data-video-loop="true" data-video-play-only-visible="true"
                            data-video-lazy-load="true" data-video-fallback-src="" data-element="wrapper"
                            style="background-position: right top; background-size: contain;
                            background-repeat: no-repeat; background-attachment: scroll;
                            border-style: none; border-width: 1px; border-radius: 0px;">
                            <div class="pagebuilder-overlay pagebuilder-poster-overlay"
                            data-overlay-color="" data-element="overlay" style="border-radius: 0px;
                            background-color: transparent; padding: 40px;">
                            <div class="pagebuilder-poster-content">
                            <div class="message" messagelaptop="" messagetablet=""
                            messagemobile="" data-element="content">
                            </div>
                            <div class="largetscreenmessage" style="display:none;">
                            </div></div></div></div></div></div></div>
                            <div class="pagebuilder-column right-column-html"
                            data-content-type="column" data-appearance="full-height"
                            data-background-images="{}" data-element="main" style="justify-content: flex-start;
                            display: flex; flex-direction: column; background-position: left top;
                            background-size: cover; background-repeat: no-repeat;
                            background-attachment: scroll; border-style: none; border-width: 1px;
                            border-radius: 0px; width: 50%; margin: 0px; padding: 10px; align-self: stretch;">
                            <div data-content-type="html" data-appearance="default" data-element="main"
                            style="border-style: none; border-width: 1px; border-radius: 0px;
                            margin: 0px; padding: 0px;">&lt;
                            div class="promo-content third-party product-pricing"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24" style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        &lt;style&gt;
                        .promo-content.third-party .promo-content-row:not(:first-child,:last-child)::before
                        { background-color:#FFF !important }
                        &lt;/style&gt;
                        </div><div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="buttons" data-appearance="inline"
                        data-same-width="false" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main"
                        style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/" target=""
                        data-link-type="default" data-element="link" style="text-align: center;">
                        <span data-element="link_text">SHOP NOW</span></a>
                        </div></div></div></div></div>
                        <div data-content-type="tab-item" data-appearance="default"
                        data-tab-name="Indoor Banners" data-background-images="{}" data-element="main"
                        id="KEE6L6Y" style="justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 40px;">
                        <div class="pagebuilder-column-group" style="display: flex;"
                        data-content-type="column-group" data-grid-size="12" data-element="main">
                        <div class="pagebuilder-column left-column-image" data-content-type="column"
                        data-appearance="full-height" data-background-images="{}" data-element="main"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: center center; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; text-align: center;
                        border-style: none; border-width: 1px; border-radius: 0px; width: 50%;
                        margin: 0px; padding: 10px; align-self: stretch;">
                        </div>
                        <div class="pagebuilder-column right-column-html"
                        data-content-type="column" data-appearance="full-height"
                        data-background-images="{}" data-element="main"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: left top; background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: none; border-width: 1px;
                        border-radius: 0px; width: 50%; margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="promo-content third-party product-pricing product-pricing-outerbox"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block product-pricing-innerbox"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24" style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                        &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px;
                        padding: 0px;">&lt;div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="buttons" data-appearance="inline"
                        data-same-width="false" data-element="main" style="border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main"
                        style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/"
                        target="" data-link-type="default" data-element="link"
                        style="text-align: center;"><span data-element="link_text">SHOP NOW</span>
                        </a></div></div></div></div></div>
                        <div data-content-type="tab-item" data-appearance="default"
                        data-tab-name="Styrene Signs" data-background-images="{}"
                        data-element="main" id="JX9GY0G" style="justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 40px;">
                        <div class="pagebuilder-column-group" style="display: flex;"
                        data-content-type="column-group" data-grid-size="12" data-element="main">
                        <div class="pagebuilder-column left-column-image" data-content-type="column"
                        data-appearance="full-height" data-background-images="{}" data-element="main"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: center center; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll;
                        text-align: center; border-style: none; border-width: 1px;
                        border-radius: 0px; width: 50%; margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="banner" data-appearance="poster"
                        data-show-button="never" data-show-overlay="never"
                        data-element="main" style="margin: 0px;">
                        <div data-element="empty_link">
                        <div class="pagebuilder-banner-wrapper"
                        data-background-images="{\&quot;desktop_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400.jpeg}}\&quot;,\&quot;mobile_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-mob-120x160.jpeg}}\&quot;,\&quot;desktop_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_1.jpeg}}\&quot;,\&quot;mobile_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-tab-150x200.jpeg}}\&quot;}"
                        data-background-type="image" data-video-loop="true"
                        data-video-play-only-visible="true" data-video-lazy-load="true"
                        data-video-fallback-src="" data-element="wrapper"
                        style="background-position: right top; background-size: contain;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: none;
                        border-width: 1px; border-radius: 0px;">
                        <div class="pagebuilder-overlay pagebuilder-poster-overlay"
                        data-overlay-color="" data-element="overlay" style="border-radius: 0px;
                        min-height: 400px; background-color: transparent; padding: 40px;">
                        <div class="pagebuilder-poster-content">
                        <div class="message" messagelaptop="" messagetablet="" messagemobile="" data-element="content">
                        <p></p><p></p></div>
                        <div class="largetscreenmessage" style="display:none;">
                        <p></p><p></p></div></div></div></div></div></div></div>
                        <div class="pagebuilder-column right-column-html"
                        data-content-type="column" data-appearance="full-height"
                        data-background-images="{}" data-element="main" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-style: none; border-width: 1px; border-radius: 0px; width: 50%;
                        margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="promo-content third-party product-pricing product-pricing-outerbox"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block product-pricing-innerbox"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24" style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                        &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$46.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$83.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="buttons" data-appearance="inline"
                        data-same-width="false" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main" style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/" target=""
                        data-link-type="default" data-element="link" style="text-align: center;">
                        <span data-element="link_text">SHOP NOW</span></a></div></div></div></div>
                        </div>
                        <div data-content-type="tab-item" data-appearance="default"
                        data-tab-name="Announcements" data-background-images="{}" data-element="main"
                        id="O1CFHX9" style="justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 40px;">
                        <div class="pagebuilder-column-group"
                        style="display: flex;" data-content-type="column-group"
                        data-grid-size="12" data-element="main">
                        <div class="pagebuilder-column left-column-image"
                        data-content-type="column" data-appearance="full-height"
                        data-background-images="{}" data-element="main"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: center center; background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; text-align: center; border-style: none;
                        border-width: 1px; border-radius: 0px; width: 50%;
                        margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="banner" data-appearance="poster"
                        data-show-button="never" data-show-overlay="never"
                        data-element="main" style="margin: 0px;">
                        <div data-element="empty_link">
                        <div class="pagebuilder-banner-wrapper"
                        data-background-images="{\&quot;desktop_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_2.jpeg}}\&quot;,\&quot;mobile_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-mob-120x160_1.jpeg}}\&quot;,
                        \&quot;desktop_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_3.jpeg}}\&quot;,\&quot;mobile_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-tab-150x200_1.jpeg}}\&quot;}"
                        data-background-type="image" data-video-loop="true" data-video-play-only-visible="true"
                        data-video-lazy-load="true" data-video-fallback-src=""
                        data-element="wrapper" style="background-position: right top;
                        background-size: contain; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: none; border-width: 1px; border-radius: 0px;">
                        <div class="pagebuilder-overlay pagebuilder-poster-overlay" data-overlay-color=""
                        data-element="overlay" style="border-radius: 0px; min-height: 400px;
                        background-color: transparent; padding: 40px;">
                        <div class="pagebuilder-poster-content">
                        <div class="message" messagelaptop="" messagetablet="" messagemobile="" data-element="content">
                        </div>
                        <div class="largetscreenmessage" style="display:none;">
                        </div></div></div></div></div></div></div>
                        <div class="pagebuilder-column right-column-html" data-content-type="column" \
                        data-appearance="full-height" data-background-images="{}" data-element="main"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: left top; background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: none; border-width: 1px;
                        border-radius: 0px; width: 50%; margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="html" data-appearance="default" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px; margin: 0px;
                        padding: 0px;">&lt;div class="promo-content third-party product-pricing"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block product-pricing-innerbox"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24" style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="buttons" data-appearance="inline"
                        data-same-width="false" data-element="main" style="border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main" style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/products-template-t6#RSBDDK3"
                        target="" data-link-type="default" data-element="link" style="text-align: center;">
                        <span data-element="link_text">SHOP NOW</span></a>
                        </div></div></div></div></div>
                        <div data-content-type="tab-item" data-appearance="default"
                        data-tab-name="Mounted Posters" data-background-images="{}"
                        data-element="main" id="O61P56X" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 40px;">
                        <div class="pagebuilder-column-group" style="display: flex;"
                        data-content-type="column-group" data-grid-size="12" data-element="main">
                        <div class="pagebuilder-column left-column-image" data-content-type="column"
                        data-appearance="full-height" data-background-images="{}"
                        data-element="main" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: center center;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        text-align: center; border-style: none; border-width: 1px;
                        border-radius: 0px; width: 50%; margin: 0px; padding: 10px; align-self: stretch;">
                        </div><div class="pagebuilder-column right-column-html" data-content-type="column"
                        data-appearance="full-height" data-background-images="{}"
                        data-element="main" style="justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: none;
                        border-width: 1px; border-radius: 0px; width: 50%;
                        margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="html" data-appearance="default" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;">
                        &lt;div class="promo-content third-party product-pricing"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block product-pricing-innerbox"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24" style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                        &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="buttons" data-appearance="inline"
                        data-same-width="false" data-element="main" style="border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main" style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/products-template-t6#RSBDDK3"
                        target="" data-link-type="default" data-element="link" style="text-align: center;">
                        <span data-element="link_text">SHOP NOW</span></a></div></div></div></div></div>
                        <div data-content-type="tab-item" data-appearance="default"
                        data-tab-name="Presentations" data-background-images="{}"
                        data-element="main" id="Y8F22BE" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 40px;">
                        <div class="pagebuilder-column-group" style="display: flex;"
                        data-content-type="column-group" data-grid-size="12" data-element="main">
                        <div class="pagebuilder-column left-column-image" data-content-type="column"
                        data-appearance="full-height" data-background-images="{}"
                        data-element="main" style="justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: center center;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        text-align: center; border-style: none; border-width: 1px; border-radius: 0px;
                        width: 50%; margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="banner" data-appearance="poster"
                        data-show-button="never" data-show-overlay="never" data-element="main"
                        style="margin: 0px;"><div data-element="empty_link">
                        <div class="pagebuilder-banner-wrapper"
                        data-background-images="{\&quot;desktop_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_4.jpeg}}\&quot;,\&quot;mobile_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-mob-120x160_2.jpeg}}\&quot;
                        ,\&quot;desktop_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_5.jpeg}}\&quot;,\&quot;mobile_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-tab-150x200_2.jpeg}}\&quot;}"
                        data-background-type="image" data-video-loop="true" data-video-play-only-visible="true"
                        data-video-lazy-load="true" data-video-fallback-src="" data-element="wrapper"
                        style="background-position: right top; background-size: contain;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: none;
                        border-width: 1px; border-radius: 0px;">
                        <div class="pagebuilder-overlay pagebuilder-poster-overlay" data-overlay-color=""
                        data-element="overlay" style="border-radius: 0px; min-height: 400px;
                        background-color: transparent;padding: 40px;">
                        <div class="pagebuilder-poster-content">
                        <div class="message" messagelaptop="" messagetablet="" messagemobile="" data-element="content">
                        </div>
                        <div class="largetscreenmessage" style="display:none;">
                        </div></div></div></div></div></div></div>
                        <div class="pagebuilder-column right-column-html" data-content-type="column"
                        data-appearance="full-height" data-background-images="{}"
                        data-element="main" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-style: none; border-width: 1px; border-radius: 0px; width: 50%; margin: 0px;
                        padding: 10px; align-self: stretch;">
                        <div data-content-type="html" data-appearance="default" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 0px;">&lt;
                        div class="promo-content third-party product-pricing"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block product-pricing-innerbox"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24" style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                        &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$159.00&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                        div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div><div data-content-type="buttons" data-appearance="inline" data-same-width="false"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main" style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/products-template-t6#RSBDDK3"
                        target="" data-link-type="default" data-element="link"
                        style="text-align: center;"><span data-element="link_text">SHOP NOW</span>
                        </a></div></div></div></div></div>
                        <div data-content-type="tab-item" data-appearance="default"
                        data-tab-name="Rubber Stamps" data-background-images="{}"
                        data-element="main" id="SALUNFE" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 40px;">
                        <div class="pagebuilder-column-group" style="display: flex;"
                        data-content-type="column-group" data-grid-size="12"
                        data-element="main">
                        <div class="pagebuilder-column left-column-image"
                        data-content-type="column" data-appearance="full-height"
                        data-background-images="{}" data-element="main"
                        style="justify-content: flex-start; display: flex;
                        flex-direction: column; background-position: center center;
                        background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; text-align: center; border-style: none;
                        border-width: 1px; border-radius: 0px; width: 50%; margin: 0px; padding: 10px;
                        align-self: stretch;">
                        <div data-content-type="banner" data-appearance="poster" data-show-button="never"
                        data-show-overlay="never" data-element="main" style="margin: 0px;">
                        <div data-element="empty_link">
                        <div class="pagebuilder-banner-wrapper"
                        data-background-images="{\&quot;desktop_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_6.jpeg}}\&quot;,\&quot;mobile_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-mob-120x160_3.jpeg}}\&quot;,\&quot;
                        desktop_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-dsk-300x400_7.jpeg}}\&quot;,\&quot;mobile_medium_image\&quot;:\&quot;
                        {{media url=wysiwyg/dsadam-tab-150x200_3.jpeg}}\&quot;}"
                        data-background-type="image" data-video-loop="true" data-video-play-only-visible="true"
                        data-video-lazy-load="true" data-video-fallback-src="" data-element="wrapper"
                        style="background-position: right top; background-size: contain;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: none;
                        border-width: 1px; border-radius: 0px;">
                        <div class="pagebuilder-overlay pagebuilder-poster-overlay" data-overlay-color=""
                        data-element="overlay" style="border-radius: 0px; min-height: 400px;
                        background-color: transparent; padding: 40px;">
                        <div class="pagebuilder-poster-content">
                        <div class="message" messagelaptop="" messagetablet="" messagemobile=""
                        data-element="content"></div>
                        <div class="largetscreenmessage" style="display:none;">
                        </div></div></div></div></div></div></div>
                        <div class="pagebuilder-column right-column-html"
                        data-content-type="column" data-appearance="full-height"
                        data-background-images="{}" data-element="main" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-style: none; border-width: 1px; border-radius: 0px; width: 50%;
                        margin: 0px; padding: 10px; align-self: stretch;">
                        <div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">
                        &lt;div class="promo-content third-party product-pricing"&gt;
                            &lt;div class="pagebuilder-column-group"&gt;
                                &lt;div class="pagebuilder-column promo-content-row m-auto" style="max-width: 500px"&gt;
                                        &lt;div class="promo-content-block product-pricing-innerbox"&gt;
                                            &lt;div class="promo-content-row table-head
                                            fedex-bold fedex-medium fs-20 lh-24"
                                            style="color: #2F4047"&gt;
                                                &lt;span class="text-left"&gt;Sizes&lt;/span&gt;
                                                &lt;span class="text-left"&gt;# of Sides&lt;/span&gt;
                                                &lt;span class="text-left"&gt;Starting Cost&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;12” X 18”&lt;/span&gt;
                                                &lt;span&gt;Single&lt;/span&gt;
                                                &lt;span&gt;$12.50&lt;/span&gt;
                                            &lt;/div&gt;
                                            &lt;div class="promo-content-row"&gt;
                                                &lt;span&gt;18” X 24”&lt;/span&gt;
                                                &lt;span&gt;Double&lt;/span&gt;
                                                &lt;span&gt;$219.00&lt;/span&gt;
                                            &lt;/div&gt;
                                        &lt;/div&gt;
                                &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div>
                            <div data-content-type="html" data-appearance="default"
                            data-element="main" style="border-style: none; border-width: 1px;
                            border-radius: 0px; margin: 0px; padding: 0px;">&lt;
                            div class="shopnow-product-pricing-container"&gt;
                            &lt;div class="show-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;span class="show-toggle"&gt;&lt;a href="#"
                                class="show-toggle-link"&gt;SHOW MORE OPTIONS&lt;/a&gt;&lt;/span&gt;
                            &lt;/div&gt;
                            &lt;div class="shop-innerbox d-inline-block fedex-bold ls-1 fs-14 lh-19"&gt;
                                &lt;button id="btn-pricing-shopnow" type="button" class="btn-secondary"&gt;
                                    &lt;a href="#"&gt;SHOP NOW&lt;/a&gt;
                                &lt;/button&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;
                        </div>
                        <div data-content-type="buttons" data-appearance="inline"
                        data-same-width="false" data-element="main" style="border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;">
                        <div class="btn-shopnow hide" data-content-type="button-item"
                        data-appearance="default" data-element="main" style="display: inline-block;">
                        <a class="pagebuilder-button-secondary"
                        href="https://staging3.office.fedex.com/default/products-template-t6#RSBDDK3"
                        target="" data-link-type="default" data-element="link" style="text-align: center;">
                        <span data-element="link_text">SHOP NOW</span></a></div></div></div></div>
                        </div></div></div></div></div>
                        <div data-content-type="row" data-appearance="contained" data-element="main">
                        <div data-enable-parallax="0" data-parallax-speed="0.5"
                        data-background-images="{}" data-background-type="image"
                        data-video-loop="true" data-video-play-only-visible="true"
                        data-video-lazy-load="true" data-video-fallback-src="" data-element="inner"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll;
                        border-style: none; border-width: 1px; border-radius: 0px; margin: 0px 0px 10px;
                        padding: 10px;">
                        <div data-content-type="html" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px; padding: 0px;">
                        &lt;script type="text/x-magento-init"&gt;
                        {
                            ".product-pricing-section-block": {
                                "Magento_PageBuilder/js/components/product-pricing-section-block": {}
                            }
                        }
                        &lt;/script&gt;
                        </div></div></div>',
            "is_active" => 1,
            "stores" => [0]
        ];

        return $blocks;
    }

    /**
     * GetG7Master Block method
     *
     * @return array
     */
    public function getG7MasterBlock($blocks)
    {
        $blocks['g7-master-printer-certification'] = [
            "title" => "G7 Master Printer Certification",
            "identifier" => "g7-master-printer-certification",
            "content" => '<div data-content-type="row" data-appearance="contained" data-element="main">
                        <div class="g7-master-print-certification" data-enable-parallax="0"
                        data-parallax-speed="0.5" data-background-images="{}" data-background-type="image"
                        data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true"
                        data-video-fallback-src="" data-element="inner" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: none;
                        border-width: 1px; border-radius: 0px; margin: 0px 0px 10px; padding: 10px;">
                        <div class="pagebuilder-column-group" style="display: flex;"
                        data-content-type="column-group" data-grid-size="12" data-element="main">
                        <div class="pagebuilder-column" data-content-type="column" data-appearance="full-height"
                        data-background-images="{}" data-element="main" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top; background-size: cover;
                        background-repeat: no-repeat; background-attachment: scroll; border-style: none;
                        border-width: 1px; border-radius: 0px; width: 16.6667%; margin: 0px; padding: 10px;
                        align-self: stretch;">
                        <figure data-content-type="image" data-appearance="full-width"
                        data-element="main" style="margin: 0px; padding: 0px; border-style: none;">
                        <a href="https://connect.idealliance.org/g7/home" target="" data-link-type="default"
                        title="" data-element="link">
                        <img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/Hnet.com-image_2_.png}}"
                        alt="" title="" data-element="desktop_image" style="border-style: none; border-width: 1px;
                        border-radius: 0px; max-width: 100%; height: auto;">
                        <img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/Hnet.com-image_2_.png}}"
                        alt="" title="" data-element="mobile_image" style="border-style: none;
                        border-width: 1px; border-radius: 0px; max-width: 100%; height: auto;"></a>
                        </figure></div>
                        <div class="pagebuilder-column" data-content-type="column" data-appearance="align-center"
                        data-background-images="{}" data-element="main" style="justify-content: flex-start;
                        display: flex; flex-direction: column; background-position: left top;
                        background-size: cover; background-repeat: no-repeat; background-attachment: scroll;
                        border-style: none; border-width: 1px; border-radius: 0px; width: 83.3333%;
                        margin: 0px; padding: 10px; align-self: center;">
                        <div class="fedex-light fs-16" data-content-type="text" data-appearance="default"
                        data-element="main" style="border-style: none; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 0px;"><p>
                        <span style="color: #2f4047;">G7 Master Qualified Printer</span></p>
                        <p><span style="color: #2f4047;">Get consistent color that’s
                        earned the industry’s premier designation for color management</span>
                        </p></div></div></div>
                        <div data-content-type="html" data-appearance="default" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px;
                        display: none; margin: 0px; padding: 0px;">&lt;style&gt;
                        .g7-master-print-certification .pagebuilder-column-group {
                            margin: auto;
                        }
                        .g7-master-print-certification .pagebuilder-column {
                            text-align: center;
                        }
                        @media only screen and (min-width: 768px) {
                            .g7-master-print-certification .pagebuilder-column {
                                text-align: left;
                            }
                            .g7-master-print-certification figure {
                                text-align: right;
                            }
                        }
                        @media only screen and (min-width: 1200px) {
                            .g7-master-print-certification .pagebuilder-column:last-child {
                                width: 900px !important;
                            }
                        }
                        &lt;/style&gt;</div></div></div>',
            "is_active" => 1,
            "stores" => [0]
        ];

        return $blocks;
    }

    /**
     * GetProductTemplateFaq Block method
     *
     * @return array
     */
    public function getProductTemplateFaqBlock($blocks)
    {
        $blocks[self::PRODUCT_PAGE_TEMPLATE_FAQ] = [
            "title" => self::PRODUCT_PAGE_TEMPLATE_FAQ,
            "identifier" => self::PRODUCT_PAGE_TEMPLATE_FAQ,
            "content" => '<div data-content-type="row" data-appearance="contained" data-element="main">
                        <div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}"
                        data-background-type="image" data-video-loop="true" data-video-play-only-visible="true"
                        data-video-lazy-load="true" data-video-fallback-src="" data-element="inner"
                        style="justify-content: flex-start; display: flex; flex-direction: column;
                        background-position: left top; background-size: cover; background-repeat: no-repeat;
                        background-attachment: scroll; border-style: none; border-width: 1px;
                        border-radius: 0px; margin: 0px 0px 10px; padding: 10px;">
                        <div data-content-type="html" data-appearance="default" data-element="main"
                        style="border-style: none; border-width: 1px; border-radius: 0px;
                        margin: 0px; padding: 0px;">&lt;
                        div class="faq faq-border fedex-light" style="padding: 5px 0px 5px 5px;"&gt;
                        &lt;div class="m-font-size-26 faq-header"&gt;
                            &lt;p style="text-align: center;"&gt;
                                &lt;span style="font-size: 32px; color: #2f4047;\"&gt;
                                    Frequently asked questions
                                &lt;/span&gt;
                            &lt;/p&gt;
                        &lt;/div&gt;
                        &lt;div data-mage-init=\'{
                                "accordion":{
                                    "collapsible": true,
                                    "multipleCollapsible": true,
                                    "animate": 200,
                                    "active": false,
                                    "icons": { "header": "collapsed", "activeHeader": "expanded" }
                                }}\'
                            class="accordion"
                            &gt;
                            &lt;div class="collapsible" data-role="collapsible"&gt;
                                &lt;div class="d-flex v-center pointer" data-role="trigger"&gt;
                                    &lt;span class="label"&gt;What can I do to find out how to make a sign?&lt;/span&gt;
                                    &lt;span class="icons" data-role="icons"&gt;&lt;/span&gt;
                                &lt;/div&gt;
                                &lt;div data-role="content"&gt; &lt;ul&gt;
                        &lt;li&gt;Get started on&amp;nbsp;FedEx Office&lt;
                        sup&gt;®&lt;/sup&gt;&amp;nbsp;Print Online&lt;/li&gt;
                        &lt;li&gt;See&amp;nbsp;&lt;
                        a href="https://www.fedex.com/en-us/printing/signs.html"
                        data-analytics="link|sign options"&gt;sign options&lt;/a&gt;&amp;nbsp;
                        to choose sizes, materials and features&lt;/li&gt;
                        &lt;li&gt;Upload your file or choose a DIY template&lt;/li&gt;
                        &lt;li&gt;Pick up your sign order at a&amp;nbsp;&lt;
                        a href="https://local.fedex.com/" data-analytics="link|FedEx Office location"&gt;
                        FedEx Office location&lt;/a&gt;&lt;/li&gt;
                        &lt;/ul&gt;
                        &lt;/div&gt;
                            &lt;/div&gt;
                            &lt;div class="collapsible" data-role="collapsible"&gt;
                                &lt;div class="d-flex v-center pointer" data-role="trigger"&gt;
                                    &lt;span class="label"&gt;Where can I
                                    learn how to make a business sign?&lt;/span&gt;
                                    &lt;span class="icons" data-role="icons"&gt;&lt;/span&gt;
                                &lt;/div&gt;
                                &lt;div data-role="content"&gt;  &lt;li&gt;
                                Create a business sign in a range of sizes, from 12" x 18" to 36" x 48"&lt;/li&gt;
                        &lt;li&gt;Choose from custom shapes for signs&lt;/li&gt;
                        &lt;li&gt;Determine indoor/outdoor or short-term/long-term use&lt;/li&gt;
                        &lt;li&gt;Sign packages include your printed sign plus a
                        display stand at a discounted price.&lt;/li&gt;
                        &lt;/ul&gt;
                        &lt;/div&gt;
                            &lt;/div&gt;
                              &lt;div class="collapsible" data-role="collapsible"&gt;
                                &lt;div class="d-flex v-center pointer" data-role="trigger"&gt;
                                    &lt;span class="label"&gt;
                                    What is the best way to determine how to make a yard sign? &lt;/span&gt;
                                    &lt;span class="icons" data-role="icons"&gt;&lt;/span&gt;
                                &lt;/div&gt;
                                &lt;div data-role="content"&gt;
                                Corrugated plastic yard signs are easy to order using FedEx Office® Print Online.
                                Choose a standard size or create a custom size. Perfect for outdoor and short-term use,
                                yard signs can be used for directional signage and may be 1 or 2-sided.
                                Select a yard sign frame and hardware. No need to know how to make your own sign;
                                FedEx Office is here to help.
                         &lt;/div&gt;
                            &lt;/div&gt;
                                &lt;div class="collapsible" data-role="collapsible"&gt;
                                &lt;div class="d-flex v-center pointer" data-role="trigger"&gt;
                                    &lt;span class="label"&gt;What are steps for how to make custom signs? &lt;/span&gt;
                                    &lt;span class="icons" data-role="icons"&gt;&lt;/span&gt;
                                &lt;/div&gt;
                                &lt;div data-role="content"&gt;
                                Our customized sign printing services include everything from birthday and
                                anniversary signs to storefront and office signs to yard signs for real estate.
                                Our online printing tool shows you how to make signs and
                                allows you to upload your own design
                                (use any image file type, including jpeg, png, tif, signs PDF, and more).
                                You can choose from hundreds of professional templates to personalize with your name,
                                logo, contact information, and more. When you want to know how to create a sign,
                                get started with FedEx Office® Print Online.&lt;/div&gt;
                            &lt;/div&gt;
                            &lt;/div&gt;
                        &lt;/div&gt;</div></div></div>',
            "is_active" => 1,
            "stores" => [0]
        ];

        return $blocks;
    }

    /**
     * DeleteBlocks By Indetifier method
     *
     * @param BlockFactory $blockIdentifier
     */
    private function deleteBlockByIdentifier($blockIdentifier)
    {
        $block = $this->blockFactory->create()->load($blockIdentifier);
        if ($block && $block->getId()) {
            $block->delete();
        }
    }

    /**
     * * AddThirdPartyContentBlocks constructor.
     *
     * @param int $heroBanner
     * @param int $shopByType
     * @param int $productPricing
     * @param int $g7Master
     * @param int $productPageTemplateFaq
     * @return array
     */
    private function getProductTemplatePage(
        $heroBanner,
        $shopByType,
        $productPricing,
        $g7Master,
        $productPageTemplateFaq
    ) {
        return [
            "title"         => "Product Template",
            "page_layout"   => "cms-full-width",
            "identifier"    => "product-template",
            "content"       => "<div data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\">
                                <div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\"
                                data-background-images=\"{}\"
                                data-background-type=\"image\" data-video-loop=\"true\"
                                data-video-play-only-visible=\"true\"
                                data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\"
                                style=\"justify-content: flex-start; display: flex; flex-direction: column;
                                background-position: left top; background-size: cover; background-repeat: no-repeat;
                                background-attachment: scroll; border-style: none;
                                border-width: 1px; border-radius: 0px;
                                margin: 0px 0px 10px; padding: 10px;\">
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$heroBanner}\"
                                type_name=\"CMS Static Block\"}}</div><div data-content-type=\"block\"
                                data-appearance=\"default\" data-element=\"main\" style=\"border-style: none;
                                border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$shopByType}\"
                                type_name=\"CMS Static Block\"}}</div><div data-content-type=\"block\"
                                data-appearance=\"default\" data-element=\"main\" style=\"border-style: none;
                                border-width: 1px; border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$productPricing}\"
                                type_name=\"CMS Static Block\"}}</div><div data-content-type=\"block\"
                                data-appearance=\"default\" data-element=\"main\"
                                style=\"border-style: none; border-width: 1px; border-radius: 0px;
                                margin: 0px; padding: 0px;\">{{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$g7Master}\"
                                type_name=\"CMS Static Block\"}}</div>
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$productPageTemplateFaq}\"
                                type_name=\"CMS Static Block\"}}</div><div data-content-type=\"html\"
                                data-appearance=\"default\" data-element=\"main\" style=\"border-style: none;
                                border-width: 0px; border-radius: 0px; margin: 0px; padding: 0px;\">
                                &lt;!-- #Custom-Breadcrumb --&gt;
                                </div></div></div>",
            "canva_size"    => '[{"record_id":"0","default":"0","product_mapping_id":"CVAPOS1068",
                               "display_width":"22\"","display_height":"28\"","orientation":"Portrait",
                               "position":"1","initialize":"true"},
                               {"record_id":"1","default":"1","product_mapping_id":"CVAPOS1069",
                                "display_width":"28\"","display_height":"22\"",
                                "orientation":"Landscape","position":"2","initialize":"true"}]',
            "is_active"     => 1,
            "has_canva_sizes"=> 0,
            "stores" => [0]
        ];
    }

    /**
     * DeleteOldProductTemplatePage method
     */
    private function deleteOldProductTemplatePage()
    {
        $oldProductTemplatePage = $this->pageFactory->create()->load('product-template');
        if ($oldProductTemplatePage && $oldProductTemplatePage->getId()) {
            $oldProductTemplatePage->delete();
        }
    }

    /**
     * GetProductTemplatePageBuilderTemplate
     *
     * @param int $heroBanner
     * @param int $shopByType
     * @param int $productPricing
     * @param int $g7Master
     * @param int $productPageTemplateFaq
     * @return array
     */
    private function getProductTemplatePageBuilderTemplate(
        $heroBanner,
        $shopByType,
        $productPricing,
        $g7Master,
        $productPageTemplateFaq
    ) {
        return [
            "name"          => "Product Template",
            "preview_image" =>  $this->staticPath . '/producttemplate620f5d8970956.jpg',
            "template"      => "<div data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\">
                               <div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\"
                                data-background-type=\"image\" data-video-loop=\"true\"
                                data-video-play-only-visible=\"true\"
                                data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\"
                                style=\"justify-content: flex-start; display: flex; flex-direction: column;
                                background-position: left top; background-size: cover; background-repeat: no-repeat;
                                background-attachment: scroll; border-style: none;
                                border-width: 1px; border-radius: 0px;
                                margin: 0px 0px 10px; padding: 10px;\">
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$heroBanner}\"
                                type_name=\"CMS Static Block\"}}</div>
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px; border-radius: 0px;
                                margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$shopByType}\"
                                type_name=\"CMS Static Block\"}}</div>
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$productPricing}\"
                                type_name=\"CMS Static Block\"}}</div>
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$g7Master}\"
                                type_name=\"CMS Static Block\"}}</div>
                                <div data-content-type=\"block\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 1px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                {{widget type=\"Magento\Cms\Block\Widget\Block\"
                                template=\"widget/static_block/default.phtml\" block_id=\"{$productPageTemplateFaq}\"
                                type_name=\"CMS Static Block\"}}</div>
                                <div data-content-type=\"html\" data-appearance=\"default\"
                                data-element=\"main\" style=\"border-style: none; border-width: 0px;
                                border-radius: 0px; margin: 0px; padding: 0px;\">
                                &lt;!-- #Custom-Breadcrumb --&gt;
                                </div></div></div>",
            "created_for"   => "any"
        ];
    }
}
