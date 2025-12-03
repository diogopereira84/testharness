<?php
declare(strict_types=1);

namespace Fedex\PageBuilderBlocks\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\Store;

/**
 * Class MarketingContentVideoTabbedBlock
 * Fedex\PageBuilderBlocks\Setup\Patch\Data
 */
class MarketingContentVideoTabbedBlock implements DataPatchInterface, PatchRevertableInterface
{
    const CMS_BLOCK_IDENTIFIER = 'marketing-content-video-tabbed-block';
    const OLD_CMS_BLOCK_IDENTIFIER = 'marketing-content-tabbed-content-block';

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
        $this->revert();
        $this->moduleDataSetup->startSetup();
        $kalturaBlockIdentfier = 'kaltura-video-player';
        $kalturaBlockId = $this->blockFactory->create()->load($kalturaBlockIdentfier)->getId();
        if ($kalturaBlockId) {
            $this->blockFactory->create()
                ->setTitle('Marketing Content Video Tabbed Block')
                ->setIdentifier(self::CMS_BLOCK_IDENTIFIER)
                ->setIsActive(true)
                ->setContent('<style>#html-body [data-pb-style=BJT2RTI]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=LKP24D2]{margin-left:30px;margin-right:30px}#html-body [data-pb-style=GWSYNML],#html-body [data-pb-style=VQ51CXU],#html-body [data-pb-style=XNWJOSC]{border-style:none;border-width:1px}#html-body [data-pb-style=O9MY3XE]{text-align:right}#html-body [data-pb-style=WTF7G6U]{border-style:none;border-width:1px;min-height:300px}#html-body [data-pb-style=I3MVPL0],#html-body [data-pb-style=I88XSMJ]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=I3MVPL0]{justify-content:flex-start;display:flex;flex-direction:column}#html-body [data-pb-style=I88XSMJ]{align-self:stretch}#html-body [data-pb-style=HQ8L6OW]{display:flex;width:100%}#html-body [data-pb-style=CCRSLRP]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:50%;align-self:stretch}#html-body [data-pb-style=OHJ7C0U]{border-style:none}#html-body [data-pb-style=DANKDHV],#html-body [data-pb-style=NCS16PH]{max-width:100%;height:auto}#html-body [data-pb-style=GJ35XN5]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:calc(50% - 20px);margin-left:20px;align-self:stretch}#html-body [data-pb-style=XQB2KRO]{text-align:left}#html-body [data-pb-style=PE88UYE]{display:inline-block}#html-body [data-pb-style=R141488]{text-align:left}#html-body [data-pb-style=N4QUDTF],#html-body [data-pb-style=NA0AMF0]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=NA0AMF0]{justify-content:flex-start;display:flex;flex-direction:column}#html-body [data-pb-style=N4QUDTF]{align-self:stretch}#html-body [data-pb-style=Y1VHE6H]{display:flex;width:100%}#html-body [data-pb-style=IHMR6W0]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:50%;align-self:stretch}#html-body [data-pb-style=XN4X6KC]{border-style:none}#html-body [data-pb-style=OURQWRQ],#html-body [data-pb-style=TU2PVYV]{max-width:100%;height:auto}#html-body [data-pb-style=WTXEYND]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:calc(50% - 20px);margin-left:20px;align-self:stretch}#html-body [data-pb-style=LMH1633]{text-align:left}#html-body [data-pb-style=PXOCF3S]{display:inline-block}#html-body [data-pb-style=MBTBGKQ]{text-align:left}#html-body [data-pb-style=BNQP4A0],#html-body [data-pb-style=I1B7UMR]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=BNQP4A0]{justify-content:flex-start;display:flex;flex-direction:column}#html-body [data-pb-style=I1B7UMR]{align-self:stretch}#html-body [data-pb-style=VI3IP4P]{display:flex;width:100%}#html-body [data-pb-style=K5EEQ3L]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:50%;align-self:stretch}#html-body [data-pb-style=RT71SWM]{border-style:none}#html-body [data-pb-style=F4NKFPB],#html-body [data-pb-style=L9YHQHU]{max-width:100%;height:auto}#html-body [data-pb-style=FRJ3DIT]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;width:calc(50% - 20px);margin-left:20px;align-self:stretch}#html-body [data-pb-style=S4LFH6G]{text-align:left}#html-body [data-pb-style=JRQVQHN]{display:inline-block}#html-body [data-pb-style=FYWWK2P]{text-align:left}@media only screen and (max-width: 768px) { #html-body [data-pb-style=OHJ7C0U],#html-body [data-pb-style=RT71SWM],#html-body [data-pb-style=XN4X6KC]{border-style:none} }</style><div  data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="BJT2RTI"><div class="new-marketing-tab-content tab-align-right" data-content-type="tabs" data-appearance="default" data-active-tab="0" data-element="main" data-pb-style="LKP24D2"><ul role="tablist" class="tabs-navigation" data-element="navigation" data-pb-style="O9MY3XE"><li role="tab" class="tab-header" data-element="headers" data-pb-style="VQ51CXU"><a href="#HPW4D8S" class="tab-title"><span class="tab-title">Online Management</span></a></li><li role="tab" class="tab-header" data-element="headers" data-pb-style="XNWJOSC"><a href="#IW2GV42" class="tab-title"><span class="tab-title">Parcel Management&nbsp;</span></a></li><li role="tab" class="tab-header" data-element="headers" data-pb-style="GWSYNML"><a href="#XFAVPDK" class="tab-title"><span class="tab-title">FedEx® OnCampus</span></a></li></ul><div class="tabs-content" data-element="content" data-pb-style="WTF7G6U"><div class="first-tab" data-content-type="tab-item" data-appearance="default" data-tab-name="Online Management" data-background-images="{}" data-element="main" id="HPW4D8S" data-pb-style="I3MVPL0"><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="I88XSMJ"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="HQ8L6OW"><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="CCRSLRP"><figure class="cms-img-wrapper video-thumbnail marketing-tab-video" data-content-type="image" data-appearance="full-width" data-wid="1_wev9t687" data-entryid="1_dkda337l" data-element="main" data-pb-style="OHJ7C0U"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/real_estate.jpg}}" alt="" title="" aria-label="" data-element="desktop_image" data-pb-style="DANKDHV"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/real_estate.jpg}}" alt="" title="" aria-label="" data-element="mobile_image" data-pb-style="NCS16PH"></figure></div><div class="pagebuilder-column new-marketing-tab-content" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="GJ35XN5"><div class="marketing-tab-content" data-content-type="text" data-appearance="default" aria-label="" data-element="main"><h3 class="elevate_workflow_title"><span style="font-size: 18pt;">Elevate your workflow with our online platform</span></h3>
<ul>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Convenience:</strong> Print on demand from anywhere, with access to files on the go.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Efficiency:</strong> Streamline workflows with admin controls, secure online access, and approval processes.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Customization:</strong> Scalable solutions tailored to business needs, including large-scale projects.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Sustainability:</strong> Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.</span></li>
</ul></div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main" data-pb-style="XQB2KRO" class="learn-more-btn-section"><div class="learn-more-btn" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="PE88UYE"><div role="button" class="pagebuilder-button-link" aria-label="learn-more-btn" data-testid="" data-element="empty_link" data-pb-style="R141488"><span aria-label="learn-more-btn" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div></div></div></div><div class="second-tab" data-content-type="tab-item" data-appearance="default" data-tab-name="Parcel Management&nbsp;" data-background-images="{}" data-element="main" id="IW2GV42" data-pb-style="NA0AMF0"><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="N4QUDTF"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="Y1VHE6H"><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="IHMR6W0"><figure class="cms-img-wrapper video-thumbnail marketing-tab-video" data-content-type="image" data-appearance="full-width" data-wid="1_wev9t687" data-entryid="1_dkda337l" data-element="main" data-pb-style="XN4X6KC"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/custom_1.jpg}}" alt="" title="" aria-label="" data-element="desktop_image" data-pb-style="TU2PVYV"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/custom_1.jpg}}" alt="" title="" aria-label="" data-element="mobile_image" data-pb-style="OURQWRQ"></figure></div><div class="pagebuilder-column new-marketing-tab-content" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="WTXEYND"><div class="marketing-tab-content" data-content-type="text" data-appearance="default" aria-label="" data-element="main"><h3 class="elevate_workflow_title"><span style="font-size: 18pt;">Elevate your workflow with our online platform</span></h3>
<ul>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Convenience:</strong> Print on demand from anywhere, with access to files on the go.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Efficiency:</strong> Streamline workflows with admin controls, secure online access, and approval processes.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Customization:</strong> Scalable solutions tailored to business needs, including large-scale projects.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Sustainability:</strong> Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.</span></li>
</ul></div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main" data-pb-style="LMH1633" class="learn-more-btn-section"><div class="learn-more-btn" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="PXOCF3S"><div role="button" class="pagebuilder-button-link" aria-label="learn-more-btn" data-testid="" data-element="empty_link" data-pb-style="MBTBGKQ"><span aria-label="learn-more-btn" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div></div></div></div><div class="third-tab" data-content-type="tab-item" data-appearance="default" data-tab-name="FedEx® OnCampus" data-background-images="{}" data-element="main" id="XFAVPDK" data-pb-style="BNQP4A0"><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="I1B7UMR"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="VI3IP4P"><div class="pagebuilder-column" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="K5EEQ3L"><figure class="cms-img-wrapper video-thumbnail marketing-tab-video" data-content-type="image" data-appearance="full-width" data-wid="1_wev9t687" data-entryid="1_dkda337l" data-element="main" data-pb-style="RT71SWM"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/certificates_2.jpg}}" alt="" title="" aria-label="" data-element="desktop_image" data-pb-style="L9YHQHU"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/certificates_2.jpg}}" alt="" title="" aria-label="" data-element="mobile_image" data-pb-style="F4NKFPB"></figure></div><div class="pagebuilder-column new-marketing-tab-content" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="FRJ3DIT"><div class="marketing-tab-content" data-content-type="text" data-appearance="default" aria-label="" data-element="main"><h3 class="elevate_workflow_title"><span style="font-size: 18pt;">Elevate your workflow with our online platform</span></h3>
<ul>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Convenience:</strong> Print on demand from anywhere, with access to files on the go.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Efficiency:</strong> Streamline workflows with admin controls, secure online access, and approval processes.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Customization:</strong> Scalable solutions tailored to business needs, including large-scale projects.</span></li>
<li style="line-height: 1.5; font-size: 10pt;"><span style="font-size: 10pt;"><strong>Sustainability:</strong> Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.</span></li>
</ul></div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main" data-pb-style="S4LFH6G" class="learn-more-btn-section"><div class="learn-more-btn" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="JRQVQHN"><div role="button" class="pagebuilder-button-link" aria-label="learn-more-btn" data-testid="" data-element="empty_link" data-pb-style="FYWWK2P"><span aria-label="learn-more-btn" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div></div></div></div></div></div></div></div><div data-content-type="block" data-appearance="default" aria-label="" data-element="main">{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="'.$kalturaBlockId.'" type_name="CMS Static Block"}}</div>')
                ->setStores([Store::DEFAULT_STORE_ID])
                ->save();
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        $marketingContentCmsBlock = $this->blockFactory->create()->load(self::OLD_CMS_BLOCK_IDENTIFIER, 'identifier');

        if ($marketingContentCmsBlock->getId()) {
            $marketingContentCmsBlock->delete();
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
