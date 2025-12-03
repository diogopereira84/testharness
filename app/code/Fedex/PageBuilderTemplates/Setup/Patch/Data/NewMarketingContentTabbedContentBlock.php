<?php

declare(strict_types=1);

namespace Fedex\PageBuilderTemplates\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Store\Model\Store;

class NewMarketingContentTabbedContentBlock implements DataPatchInterface, PatchRevertableInterface
{
    const CMS_BLOCK_IDENTIFIER = 'marketing-content-tabbed-content-block';

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
        $kalturaBlockIdentfier = 'kaltura-video-player';
        $kalturaBlockId = $this->blockFactory->create()->load($kalturaBlockIdentfier)->getId();
        if($kalturaBlockId) {
            $this->blockFactory->create()
            ->setTitle('Marketing Content Tabbed Content Block')
            ->setIdentifier(self::CMS_BLOCK_IDENTIFIER)
            ->setIsActive(true)
            ->setContent('<style>#html-body [data-pb-style=L4ROJ1A],#html-body [data-pb-style=N46RJCF],#html-body [data-pb-style=SAIMY0P]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=L4ROJ1A],#html-body [data-pb-style=N46RJCF]{justify-content:flex-start;display:flex;flex-direction:column}#html-body [data-pb-style=SAIMY0P]{padding:20px;align-self:stretch}#html-body [data-pb-style=PVHMREM]{display:flex;width:100%}#html-body [data-pb-style=K88HA3V],#html-body [data-pb-style=YJCY3IE]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;min-height:420px;width:50%;align-self:stretch}#html-body [data-pb-style=YJCY3IE]{padding:24px 40px}#html-body [data-pb-style=H60BB46]{text-align:center;margin:0;padding:0;border-style:none}#html-body [data-pb-style=RBKA6QX],#html-body [data-pb-style=RGEH5PL]{max-width:100%;height:auto}#html-body [data-pb-style=BT9AOMX],#html-body [data-pb-style=I0DQQPI],#html-body [data-pb-style=UT0ALCG]{border-style:none;border-width:1px}#html-body [data-pb-style=XLF7DMW]{text-align:left}#html-body [data-pb-style=U1U4MKP]{border-style:none;border-width:1px;min-height:300px}#html-body [data-pb-style=DUDBAVQ],#html-body [data-pb-style=R8BLYKI],#html-body [data-pb-style=T55I3PE]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=CRWEQ14]{display:inline-block}#html-body [data-pb-style=NX45P1R]{text-align:center}#html-body [data-pb-style=TMMEVHM]{display:inline-block}#html-body [data-pb-style=KEGCP3J]{text-align:center}#html-body [data-pb-style=XAQTOWL]{display:inline-block}#html-body [data-pb-style=DLCWSPU]{text-align:center}@media only screen and (max-width: 768px) { #html-body [data-pb-style=K88HA3V]{display:flex;flex-direction:column;min-height:200px;align-self:stretch}#html-body [data-pb-style=H60BB46]{border-style:none} }</style><div id="marketing-content-tabbed-content-block" data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="L4ROJ1A"><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="SAIMY0P"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="PVHMREM"><div class="pagebuilder-column custom_block_online_video" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="K88HA3V"><figure class="cms-img-wrapper video-thumbnail" data-content-type="image" data-appearance="full-width" data-wid="1_wev9t687" data-entryid="1_dkda337l" data-element="main" data-pb-style="H60BB46"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/Untitled_5.png}}" alt="" title="" aria-label="" data-element="desktop_image" data-pb-style="RBKA6QX"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/Untitled_5.png}}" alt="" title="" aria-label="" data-element="mobile_image" data-pb-style="RGEH5PL"></figure></div><div class="pagebuilder-column custom_block_online" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="YJCY3IE"><div class="custom_block_online_tabs tab-align-left" data-content-type="tabs" data-appearance="default" data-active-tab="0" data-element="main"><ul role="tablist" class="tabs-navigation" data-element="navigation" data-pb-style="XLF7DMW"><li role="tab" class="tab-header" data-element="headers" data-pb-style="BT9AOMX"><a href="#Q0BL9O9" class="tab-title"><span class="tab-title">Online Management</span></a></li><li role="tab" class="tab-header" data-element="headers" data-pb-style="I0DQQPI"><a href="#S0ANXHV" class="tab-title"><span class="tab-title">Parcel Management</span></a></li><li role="tab" class="tab-header" data-element="headers" data-pb-style="UT0ALCG"><a href="#F3L7U31" class="tab-title"><span class="tab-title">FedEx® OnCampus</span></a></li></ul><div class="tabs-content" data-element="content" data-pb-style="U1U4MKP"><div data-content-type="tab-item" data-appearance="default" data-tab-name="Online Management" data-background-images="{}" data-element="main" id="Q0BL9O9" data-pb-style="R8BLYKI"><div data-content-type="html" data-appearance="default" data-element="main">&lt;h3 class="elevate_workflow_title"&gt;Elevate your workflow with our online platform&lt;/h3&gt;
&lt;div class="elevate_workflow_content"&gt;
&lt;p&gt;&lt;strong&gt;. Convenience:&lt;/strong&gt; Print on demand from anywhere, with access to files on the go.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Efficiency:&lt;/strong&gt; Streamline workflows with admin controls, secure online access, and approval processes.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Customization:&lt;/strong&gt; Scalable solutions tailored to business needs, including large-scale projects.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Sustainability:&lt;/strong&gt; Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.&lt;/p&gt;
&lt;/div&gt;</div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main"><div class="custom_block_online_button" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="CRWEQ14"><div role="button" class="pagebuilder-button-link" aria-label="" data-testid="" data-element="empty_link" data-pb-style="NX45P1R"><span aria-label="" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div><div data-content-type="tab-item" data-appearance="default" data-tab-name="Parcel Management" data-background-images="{}" data-element="main" id="S0ANXHV" data-pb-style="DUDBAVQ"><div data-content-type="html" data-appearance="default" data-element="main">&lt;h3 class="elevate_workflow_title"&gt;Elevate your workflow with our online platform&lt;/h3&gt;
&lt;div class="elevate_workflow_content"&gt;
&lt;p&gt;&lt;strong&gt;. Convenience:&lt;/strong&gt; Print on demand from anywhere, with access to files on the go.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Efficiency:&lt;/strong&gt; Streamline workflows with admin controls, secure online access, and approval processes.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Customization:&lt;/strong&gt; Scalable solutions tailored to business needs, including large-scale projects.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Sustainability:&lt;/strong&gt; Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.&lt;/p&gt;
&lt;/div&gt;</div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main"><div class="custom_block_online_button" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="TMMEVHM"><div role="button" class="pagebuilder-button-link" aria-label="" data-testid="" data-element="empty_link" data-pb-style="KEGCP3J"><span aria-label="" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div><div data-content-type="tab-item" data-appearance="default" data-tab-name="FedEx® OnCampus" data-background-images="{}" data-element="main" id="F3L7U31" data-pb-style="T55I3PE"><div data-content-type="html" data-appearance="default" data-element="main">&lt;h3 class="elevate_workflow_title"&gt;Elevate your workflow with our online platform&lt;/h3&gt;
&lt;div class="elevate_workflow_content"&gt;
&lt;p&gt;&lt;strong&gt;. Convenience:&lt;/strong&gt; Print on demand from anywhere, with access to files on the go.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Efficiency:&lt;/strong&gt; Streamline workflows with admin controls, secure online access, and approval processes.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Customization:&lt;/strong&gt; Scalable solutions tailored to business needs, including large-scale projects.&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;. Sustainability:&lt;/strong&gt; Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.&lt;/p&gt;
&lt;/div&gt;</div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main"><div class="custom_block_online_button" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="XAQTOWL"><div role="button" class="pagebuilder-button-link" aria-label="" data-testid="" data-element="empty_link" data-pb-style="DLCWSPU"><span aria-label="" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div></div></div></div></div></div></div></div><div id="marketing-content-tabbed-content-block" data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="N46RJCF"><div data-content-type="block" data-appearance="default" aria-label="" data-element="main">{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="'.$kalturaBlockId.'" type_name="CMS Static Block"}}</div></div></div>')
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
        $marketingContentCmsBlock = $this->blockFactory
            ->create()
            ->load(self::CMS_BLOCK_IDENTIFIER, 'identifier');

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
