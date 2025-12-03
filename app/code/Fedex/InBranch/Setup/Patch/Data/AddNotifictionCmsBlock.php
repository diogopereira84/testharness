<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\InBranch\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\Store;

class AddNotifictionCmsBlock implements DataPatchInterface, PatchRevertableInterface
{
    public const CMS_BLOCK_IDENTIFIER = 'in-branch-mixed-location-warning-new';

    public const CMS_BLOCK_OLD_IDENTIFIER = 'in-branch-mixed-location-warning';

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

        $block = $this->blockFactory->create()->load(self::CMS_BLOCK_OLD_IDENTIFIER);
        if ($block && $block->getId()) {
            $block->delete();
        }

        $this->blockFactory->create()
            ->setTitle('In-Branch Mixed Cart Notification')
            ->setIdentifier(self::CMS_BLOCK_IDENTIFIER)
            ->setIsActive(true)
            ->setContent('<style>#html-body [data-pb-style=LKFYE7D]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;border-style:none;border-width:1px;border-radius:0;margin:0 0 10px;padding:10px}#html-body [data-pb-style=UTFUPAN]{margin:0;padding:0;border-style:none}#html-body [data-pb-style=AVV08WO],#html-body [data-pb-style=OYTI25Q]{border-style:none;border-width:1px;border-radius:0;max-width:100%;height:auto}#html-body [data-pb-style=AKHMF2Q],#html-body [data-pb-style=ERIN6UU]{border-style:none;border-width:1px;border-radius:0;margin:0;padding:0}#html-body [data-pb-style=ERIN6UU]{padding:10px 10px 0}#html-body [data-pb-style=GHWKII0]{display:inline-block}#html-body [data-pb-style=OE3DLCM]{text-align:center}@media only screen and (max-width: 768px) { #html-body [data-pb-style=UTFUPAN]{border-style:none} }</style><div id="in-branch-mixed-location-warning" data-content-type="row" data-appearance="contained" data-element="main"><div class="cart-warning-popup-main" data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="LKFYE7D"><figure class="cart-warning-icon" data-content-type="image" data-appearance="full-width" data-element="main" data-pb-style="UTFUPAN"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/Warning_Icon_Outline.png}}" alt="" title="" data-element="desktop_image" data-pb-style="AVV08WO"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/Warning_Icon_Outline.png}}" alt="" title="" data-element="mobile_image" data-pb-style="OYTI25Q"></figure><div class="cart-warning-description" data-content-type="text" data-appearance="default" data-element="main" data-pb-style="AKHMF2Q"><p>We\'re unable to combine this product with items already in your cart. Please complete your existing order, and return back to this tab to complete this order.</p></div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main" data-pb-style="ERIN6UU" class="btn-warning-popup"><div class="cart-warning-btn-text" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="GHWKII0"><a role="link" class="pagebuilder-button-primary" href="/ondemand/checkout/cart" data-link-type="default" aria-label="" data-testid="" data-element="link" data-pb-style="OE3DLCM"><span aria-label="" data-testid="" data-element="link_text">GO TO CART</span></a></div></div></div></div>')
            ->setStores([Store::DEFAULT_STORE_ID])
            ->save();

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
