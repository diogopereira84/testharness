<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\AllPrintProducts\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\Store;

class AddSpecialityProductsBlock implements DataPatchInterface, PatchRevertableInterface
{
    const CMS_BLOCK_IDENTIFIER = 'all-print-products-special-products';

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
            ->setTitle('All Print Product Page Speciality Products Block')
            ->setIdentifier(self::CMS_BLOCK_IDENTIFIER)
            ->setIsActive(true)
            ->setContent('<style>#html-body [data-pb-style=JRSEMQS]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;margin-bottom:48px}#html-body [data-pb-style=MN9EU39]{text-align:center}#html-body [data-pb-style=O894O71]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;align-self:stretch}#html-body [data-pb-style=XPGO8UD]{display:flex;width:100%}#html-body [data-pb-style=C2F43NU],#html-body [data-pb-style=KJN5T7K],#html-body [data-pb-style=LK8NYQJ],#html-body [data-pb-style=MTT9CDI]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:25%;align-self:stretch}</style><div id="all-print-products-special-products" data-content-type="row" data-appearance="contained" data-element="main"><div class="image-slider-block" data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="JRSEMQS"><h2 data-content-type="heading" data-appearance="default" aria-label="" data-element="main" data-pb-style="MN9EU39">Speciality Products</h2><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="O894O71"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="XPGO8UD"><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="LK8NYQJ"><figure class="speciality-products-item" data-content-type="image" data-appearance="full-width" data-wid="" data-entryid="" data-element="main"></figure></div><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="MTT9CDI"><figure class="speciality-products-item" data-content-type="image" data-appearance="full-width" data-wid="" data-entryid="" data-element="main"></figure></div><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="KJN5T7K"><figure class="speciality-products-item" data-content-type="image" data-appearance="full-width" data-wid="" data-entryid="" data-element="main"></figure></div><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="C2F43NU"><figure class="speciality-products-item" data-content-type="image" data-appearance="full-width" data-wid="" data-entryid="" data-element="main"></figure></div></div></div></div></div>')->setStores([Store::DEFAULT_STORE_ID])
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
