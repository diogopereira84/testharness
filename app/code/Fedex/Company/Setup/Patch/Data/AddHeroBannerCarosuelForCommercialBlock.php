<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\Store;

class AddHeroBannerCarosuelForCommercialBlock implements DataPatchInterface, PatchRevertableInterface
{
    const CMS_BLOCK_IDENTIFIER = 'commercial-hero-banner-carousel';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private BlockFactory $blockFactory
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $this->blockFactory->create()
            ->setTitle('Commercial Hero Banner Carousel')
            ->setIdentifier(self::CMS_BLOCK_IDENTIFIER)
            ->setIsActive(true)
            ->setContent('<style>#html-body [data-pb-style=TQW4KSE]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=IS6BNFB]{min-height:196px}#html-body [data-pb-style=BY26CEG]{background-position:left top;background-size:cover;background-repeat:no-repeat}#html-body [data-pb-style=UV1JYHQ]{background-color:transparent}#html-body [data-pb-style=UTULVGF],#html-body [data-pb-style=YWSLK67]{opacity:1;visibility:visible}#html-body [data-pb-style=ODTO7YV]{background-position:left top;background-size:cover;background-repeat:no-repeat}#html-body [data-pb-style=B5R09P9]{background-color:transparent}#html-body [data-pb-style=CX32H6W],#html-body [data-pb-style=QWLXO9O]{opacity:1;visibility:visible}#html-body [data-pb-style=HIWE4YG]{background-position:left top;background-size:cover;background-repeat:no-repeat}#html-body [data-pb-style=HCW7SKH]{background-color:transparent}#html-body [data-pb-style=CP81ARD],#html-body [data-pb-style=LN3CXVD]{opacity:1;visibility:visible}</style><div id="commercial-hero-banner-carousel" data-content-type="row" data-appearance="contained" data-element="main"><div class="selfreg-hero-banner-wrapper" data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="TQW4KSE"><div class="pagebuilder-slider retail-home-banner-section" data-content-type="slider" data-appearance="default" data-autoplay="true" data-autoplay-speed="2000" data-fade="false" data-infinite-loop="true" data-show-arrows="true" data-show-dots="true" aria-label="" data-element="main" data-pb-style="IS6BNFB"><div data-content-type="slide" data-slide-name="Slide 1" data-appearance="poster" data-show-button="always" data-show-overlay="never" foreground_image_toggle="no" foreground_laptop_image_toggle="yes" foreground_tablet_image_toggle="yes" data-element="main"><a href="/configurator/index/index?id=1534436209752-4-3" target="" data-link-type="default" title="" aria-label="" data-element="link"><div class="pagebuilder-slide-wrapper" data-background-images="{\&quot;desktop_image\&quot;:\&quot;{{media url=wysiwyg/5313454a799407179bf485c221c48b35_2_.png}}\&quot;,\&quot;mobile_image\&quot;:\&quot;{{media url=wysiwyg/_REPLACE_IMAGE_.png}}\&quot;,\&quot;desktop_medium_image\&quot;:\&quot;{{media url=wysiwyg/5313454a799407179bf485c221c48b35_2__1.png}}\&quot;,\&quot;mobile_medium_image\&quot;:\&quot;{{media url=wysiwyg/e7b70476c25c8f012d90bd484b8582d8.png}}\&quot;,\&quot;foreground_image_toggle\&quot;:\&quot;no\&quot;,\&quot;foreground_laptop_image_toggle\&quot;:\&quot;yes\&quot;,\&quot;foreground_tablet_image_toggle\&quot;:\&quot;yes\&quot;}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="wrapper" data-pb-style="BY26CEG"><div class="pagebuilder-overlay pagebuilder-poster-overlay" data-overlay-color="" aria-label="" title="" data-element="overlay" data-pb-style="UV1JYHQ"><div class="pagebuilder-poster-content hero-banner-pagebuilder-content" style="display: none;"><div class="hero-banner-desktop-message" aria-label="" data-element="content"><p data-sider-select-id="3d181119-30db-4d13-a1ef-fcfe522b4ceb">Find the signs you need for a sucessful season</p></div><div class="hero-banner-laptop-message" data-element="messagelaptop"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-tablet-message" data-element="messagetablet"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-mobile-message" data-element="messagemobile"><h1>Find the signs you need for a sucessful season</h1></div><button type="button" class="pagebuilder-slide-button pagebuilder-button-primary" aria-label="" data-element="button" data-pb-style="YWSLK67">GET STARTED</button></div></div></div></a><div class="mobile-hero-banner-poster-content" style="display: none;"><div class="mobile-hero-banner-content" style="display: none;"><div class="hero-banner-desktop-message" aria-label="" data-element="content"><p data-sider-select-id="3d181119-30db-4d13-a1ef-fcfe522b4ceb">Find the signs you need for a sucessful season</p></div><div class="hero-banner-laptop-message" data-element="messagelaptop"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-tablet-message" data-element="messagetablet"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-mobile-message" data-element="messagemobile"><h1>Find the signs you need for a sucessful season</h1></div><button type="button" class="mobile-pagebuilder-slide-button pagebuilder-button-primary" aria-label="" data-element="button" data-pb-style="UTULVGF">GET STARTED</button></div></div></div><div data-content-type="slide" data-slide-name="Slide 1" data-appearance="poster" data-show-button="always" data-show-overlay="never" foreground_image_toggle="no" foreground_laptop_image_toggle="no" foreground_tablet_image_toggle="no" data-element="main"><a href="/configurator/index/index?id=1534436209752-4-3" target="" data-link-type="default" title="" aria-label="" data-element="link"><div class="pagebuilder-slide-wrapper" data-background-images="{\&quot;desktop_image\&quot;:\&quot;{{media url=wysiwyg/fff_9_.png}}\&quot;,\&quot;foreground_image_toggle\&quot;:\&quot;no\&quot;,\&quot;foreground_laptop_image_toggle\&quot;:\&quot;no\&quot;,\&quot;foreground_tablet_image_toggle\&quot;:\&quot;no\&quot;}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="wrapper" data-pb-style="ODTO7YV"><div class="pagebuilder-overlay pagebuilder-poster-overlay" data-overlay-color="" aria-label="" title="" data-element="overlay" data-pb-style="B5R09P9"><div class="pagebuilder-poster-content hero-banner-pagebuilder-content" style="display: none;"><div class="hero-banner-desktop-message" aria-label="" data-element="content"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-laptop-message" data-element="messagelaptop"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-tablet-message" data-element="messagetablet"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-mobile-message" data-element="messagemobile"><h1>Find the signs you need for a sucessful season</h1></div><button type="button" class="pagebuilder-slide-button pagebuilder-button-primary" aria-label="" data-element="button" data-pb-style="CX32H6W">GET STARTED</button></div></div></div></a><div class="mobile-hero-banner-poster-content" style="display: none;"><div class="mobile-hero-banner-content" style="display: none;"><div class="hero-banner-desktop-message" aria-label="" data-element="content"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-laptop-message" data-element="messagelaptop"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-tablet-message" data-element="messagetablet"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-mobile-message" data-element="messagemobile"><h1>Find the signs you need for a sucessful season</h1></div><button type="button" class="mobile-pagebuilder-slide-button pagebuilder-button-primary" aria-label="" data-element="button" data-pb-style="QWLXO9O">GET STARTED</button></div></div></div><div data-content-type="slide" data-slide-name="Slide 1" data-appearance="poster" data-show-button="always" data-show-overlay="never" data-element="main"><a href="/configurator/index/index?id=1534436209752-4-3" target="" data-link-type="default" title="" aria-label="" data-element="link"><div class="pagebuilder-slide-wrapper" data-background-images="{\&quot;desktop_image\&quot;:\&quot;{{media url=wysiwyg/5313454a799407179bf485c221c48b35_2.png}}\&quot;}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="wrapper" data-pb-style="HIWE4YG"><div class="pagebuilder-overlay pagebuilder-poster-overlay" data-overlay-color="" aria-label="" title="" data-element="overlay" data-pb-style="HCW7SKH"><div class="pagebuilder-poster-content hero-banner-pagebuilder-content" style="display: none;"><div class="hero-banner-desktop-message" aria-label="" data-element="content"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-laptop-message" data-element="messagelaptop"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-tablet-message" data-element="messagetablet"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-mobile-message" data-element="messagemobile"><h1>Find the signs you need for a sucessful season</h1></div><button type="button" class="pagebuilder-slide-button pagebuilder-button-primary" aria-label="" data-element="button" data-pb-style="LN3CXVD">GET STARTED</button></div></div></div></a><div class="mobile-hero-banner-poster-content" style="display: none;"><div class="mobile-hero-banner-content" style="display: none;"><div class="hero-banner-desktop-message" aria-label="" data-element="content"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-laptop-message" data-element="messagelaptop"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-tablet-message" data-element="messagetablet"><p>Find the signs you need for a sucessful season</p></div><div class="hero-banner-mobile-message" data-element="messagemobile"><h1>Find the signs you need for a sucessful season</h1></div><button type="button" class="mobile-pagebuilder-slide-button pagebuilder-button-primary" aria-label="" data-element="button" data-pb-style="CP81ARD">GET STARTED</button></div></div></div></div></div></div>')->save();

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $sampleCmsBlock = $this->blockFactory
            ->create()
            ->load(self::CMS_BLOCK_IDENTIFIER, 'identifier');

        if ($sampleCmsBlock->getId()) {
            $sampleCmsBlock->delete();
        }
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
