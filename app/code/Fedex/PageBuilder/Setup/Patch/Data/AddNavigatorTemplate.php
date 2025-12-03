<?php

namespace Fedex\PageBuilder\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\PageBuilder\Model\TemplateFactory;

/**
 * @codeCoverageIgnore
 */
class AddNavigatorTemplate implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * pub static path
     */
    protected $staticPath = "../static/adminhtml/Magento/backend/en_US/Fedex_PageBuilder/images";

    /**
     * @var \Magento\PageBuilder\Model\TemplateFactory
     */
    private $_templateFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param TemplateFactory $templateFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        TemplateFactory $templateFactory
    ) {
        $this->_templateFactory = $templateFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $data = [
            'template_id' => null,
            "name" => "webjump-links-template",
            "preview_image" => "$this->staticPath/template-managerwebjumplinkstemplate63e644e49e18c.jpg",
            "template" => "<style>#html-body [data-pb-style=HEM6UYU]{text-align:center}#html-body [data-pb-style=DYO1SR6],#html-body [data-pb-style=HEM6UYU],#html-body [data-pb-style=J4YE1DH],#html-body [data-pb-style=M76VDMM],#html-body [data-pb-style=R5U71Q5],#html-body [data-pb-style=RO2NL4P],#html-body [data-pb-style=TOI1NJ0]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=KEDAK3P]{border-style:none}#html-body [data-pb-style=LQSJMLT],#html-body [data-pb-style=N1D0GNH]{max-width:100%;height:auto}#html-body [data-pb-style=GEKVC7C]{text-align:center}@media only screen and (max-width: 768px) { #html-body [data-pb-style=KEDAK3P]{border-style:none} }</style><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"HEM6UYU\"><figure data-content-type=\"image\" data-appearance=\"full-width\" data-wid=\"\" data-entryid=\"\" data-element=\"main\" data-pb-style=\"KEDAK3P\"><img class=\"pagebuilder-mobile-hidden\" src=\"{{media url=wysiwyg/fedex-banner.jpg}}\" alt=\"\" title=\"\" aria-label=\"\" data-element=\"desktop_image\" data-pb-style=\"N1D0GNH\"><img class=\"pagebuilder-mobile-only\" src=\"{{media url=wysiwyg/fedex-banner.jpg}}\" alt=\"\" title=\"\" aria-label=\"\" data-element=\"mobile_image\" data-pb-style=\"LQSJMLT\"></figure></div></div><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div class=\"webpage-jumplinks\" data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"J4YE1DH\"><div data-content-type=\"text\" data-appearance=\"default\" aria-label=\"\" data-element=\"main\" data-pb-style=\"GEKVC7C\"><ul><li style=\"text-align: center;\"><a tabindex=\"0\" href=\"#shop-by-type\">Shop By Type</a></li><li style=\"text-align: center;\"><a tabindex=\"0\" href=\"#product-pricing\">Product Pricing</a></li><li style=\"text-align: center;\"><a tabindex=\"0\" href=\"#retail-home-banner-staging2\">Retail Banner</a></li><li style=\"text-align: center;\"><a tabindex=\"0\" href=\"#hero-banner-product-template\">Hero Banner</a></li><li style=\"text-align: center;\"><a tabindex=\"0\" href=\"#upload_print_products\">Upload Print</a></li></ul></div></div></div><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"RO2NL4P\"><div data-content-type=\"block\" data-appearance=\"default\" aria-label=\"\" data-element=\"main\">{{widget type=\"Magento\Cms\Block\Widget\Block\" template=\"widget/static_block/default.phtml\" block_id=\"358\" type_name=\"CMS Static Block\"}}</div></div></div><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"DYO1SR6\"><div data-content-type=\"block\" data-appearance=\"default\" aria-label=\"\" data-element=\"main\">{{widget type=\"Magento\Cms\Block\Widget\Block\" template=\"widget/static_block/default.phtml\" block_id=\"357\" type_name=\"CMS Static Block\"}}</div></div></div><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"R5U71Q5\"><div data-content-type=\"block\" data-appearance=\"default\" aria-label=\"\" data-element=\"main\">{{widget type=\"Magento\Cms\Block\Widget\Block\" template=\"widget/static_block/default.phtml\" block_id=\"259\" type_name=\"CMS Static Block\"}}</div></div></div><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"M76VDMM\"><div data-content-type=\"block\" data-appearance=\"default\" aria-label=\"\" data-element=\"main\">{{widget type=\"Magento\Cms\Block\Widget\Block\" template=\"widget/static_block/default.phtml\" block_id=\"356\" type_name=\"CMS Static Block\"}}</div></div></div><div id=\"%identifier%\" data-content-type=\"row\" data-appearance=\"contained\" data-element=\"main\"><div data-enable-parallax=\"0\" data-parallax-speed=\"0.5\" data-background-images=\"{}\" data-background-type=\"image\" data-video-loop=\"true\" data-video-play-only-visible=\"true\" data-video-lazy-load=\"true\" data-video-fallback-src=\"\" data-element=\"inner\" data-pb-style=\"TOI1NJ0\"><div data-content-type=\"block\" data-appearance=\"default\" aria-label=\"\" data-element=\"main\">{{widget type=\"Magento\Cms\Block\Widget\Block\" template=\"widget/static_block/default.phtml\" block_id=\"8\" type_name=\"CMS Static Block\"}}</div></div></div>",
            "created_for" => "page",
            'created_at' => null,
            'updated_at' => null,
        ];
        $post = $this->_templateFactory->create();
        $post->addData($data)->save();

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}

