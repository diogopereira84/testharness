<?php

namespace Fedex\PageBuilderTemplates\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Cms\Model\BlockFactory;

class NewMarketingContentTabbedContent implements DataPatchInterface
{
    /** pub static path */
    protected $staticPath = "../static/adminhtml/Magento/backend/en_US/Fedex_PageBuilderTemplates/images";

    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private BlockFactory $blockFactory
    )
    {
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        // Ensure the table exists
        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $connection->getTableName('pagebuilder_template');

        if (!$connection->isTableExists($tableName)) {
            throw new LocalizedException(__('Table %1 does not exist.', $tableName)); // Updated exception
        }

        $kalturaBlockIdentfier = 'kaltura-video-player';
        $kalturaBlockId = $this->blockFactory->create()->load($kalturaBlockIdentfier)->getId();
        // Define your template data here
        if($kalturaBlockId) {
            $templateData = [
                'template_id' => null,
                'name' => 'Marketing Content Tabbed Content',
                'template' => '<style>#html-body [data-pb-style=EW4FKCX],#html-body [data-pb-style=KMAVK2A]{background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=EW4FKCX]{justify-content:flex-start;display:flex;flex-direction:column}#html-body [data-pb-style=KMAVK2A]{padding:20px;align-self:stretch}#html-body [data-pb-style=F0FJ21W]{display:flex;width:100%}#html-body [data-pb-style=UXUI42S]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;min-height:420px;width:50%;align-self:stretch}#html-body [data-pb-style=MHGYR36]{text-align:center;margin:0;padding:0;border-style:none}#html-body [data-pb-style=BPYB096],#html-body [data-pb-style=I9QLBL3]{max-width:100%;height:auto}#html-body [data-pb-style=WMSVYKS]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll;min-height:420px;width:50%;padding:24px 40px;align-self:stretch}#html-body [data-pb-style=B5E1LL6],#html-body [data-pb-style=RPUEJRW],#html-body [data-pb-style=Y7WV9AS]{border-style:none;border-width:1px}#html-body [data-pb-style=DHXQP77]{text-align:left}#html-body [data-pb-style=HT1MB0N]{border-style:none;border-width:1px;min-height:300px}#html-body [data-pb-style=YLETB96]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=B44B1OF]{display:inline-block}#html-body [data-pb-style=G4PGW67]{text-align:center}#html-body [data-pb-style=R6VTWML]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=MW4OL4S]{display:inline-block}#html-body [data-pb-style=XEQPKHO]{text-align:center}#html-body [data-pb-style=IHKCP3K]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}#html-body [data-pb-style=WMD6H0V]{display:inline-block}#html-body [data-pb-style=NPC757D]{text-align:center}#html-body [data-pb-style=Q6VCSL0]{justify-content:flex-start;display:flex;flex-direction:column;background-position:left top;background-size:cover;background-repeat:no-repeat;background-attachment:scroll}@media only screen and (max-width: 768px) { #html-body [data-pb-style=UXUI42S]{display:flex;flex-direction:column;min-height:200px;align-self:stretch}#html-body [data-pb-style=MHGYR36]{border-style:none} }</style><div id="%identifier%" data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="EW4FKCX"><div class="pagebuilder-column-group" data-background-images="{}" data-content-type="column-group" data-appearance="default" data-grid-size="12" data-element="main" data-pb-style="KMAVK2A"><div class="pagebuilder-column-line" data-content-type="column-line" data-element="main" data-pb-style="F0FJ21W"><div class="pagebuilder-column custom_block_online_video" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="UXUI42S"><figure class="cms-img-wrapper video-thumbnail" data-content-type="image" data-appearance="full-width" data-wid="1_wev9t687" data-entryid="1_dkda337l" data-element="main" data-pb-style="MHGYR36"><img class="pagebuilder-mobile-hidden" src="{{media url=wysiwyg/Untitled_5.png}}" alt="" title="" aria-label="" data-element="desktop_image" data-pb-style="BPYB096"><img class="pagebuilder-mobile-only" src="{{media url=wysiwyg/Untitled_5.png}}" alt="" title="" aria-label="" data-element="mobile_image" data-pb-style="I9QLBL3"></figure></div><div class="pagebuilder-column custom_block_online" data-content-type="column" data-appearance="full-height" data-background-images="{}" data-element="main" data-pb-style="WMSVYKS"><div class="custom_block_online_tabs tab-align-left" data-content-type="tabs" data-appearance="default" data-active-tab="0" data-element="main"><ul role="tablist" class="tabs-navigation" data-element="navigation" data-pb-style="DHXQP77"><li role="tab" class="tab-header" data-element="headers" data-pb-style="RPUEJRW"><a href="#AHWL48L" class="tab-title"><span class="tab-title">Online Management</span></a></li><li role="tab" class="tab-header" data-element="headers" data-pb-style="Y7WV9AS"><a href="#I8DD6SJ" class="tab-title"><span class="tab-title">Parcel Management</span></a></li><li role="tab" class="tab-header" data-element="headers" data-pb-style="B5E1LL6"><a href="#HYCR905" class="tab-title"><span class="tab-title">FedEx® OnCampus</span></a></li></ul><div class="tabs-content" data-element="content" data-pb-style="HT1MB0N"><div data-content-type="tab-item" data-appearance="default" data-tab-name="Online Management" data-background-images="{}" data-element="main" id="AHWL48L" data-pb-style="YLETB96"><div data-content-type="html" data-appearance="default" data-element="main">&lt;h3 class="elevate_workflow_title"&gt;Elevate your workflow with our online platform&lt;/h3&gt;
    &lt;div class="elevate_workflow_content"&gt;
    &lt;p&gt;&lt;strong&gt;. Convenience:&lt;/strong&gt; Print on demand from anywhere, with access to files on the go.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Efficiency:&lt;/strong&gt; Streamline workflows with admin controls, secure online access, and approval processes.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Customization:&lt;/strong&gt; Scalable solutions tailored to business needs, including large-scale projects.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Sustainability:&lt;/strong&gt; Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.&lt;/p&gt;
    &lt;/div&gt;</div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main"><div class="custom_block_online_button" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="B44B1OF"><div role="button" class="pagebuilder-button-link" aria-label="" data-testid="" data-element="empty_link" data-pb-style="G4PGW67"><span aria-label="" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div><div data-content-type="tab-item" data-appearance="default" data-tab-name="Parcel Management" data-background-images="{}" data-element="main" id="I8DD6SJ" data-pb-style="R6VTWML"><div data-content-type="html" data-appearance="default" data-element="main">&lt;h3 class="elevate_workflow_title"&gt;Elevate your workflow with our online platform&lt;/h3&gt;
    &lt;div class="elevate_workflow_content"&gt;
    &lt;p&gt;&lt;strong&gt;. Convenience:&lt;/strong&gt; Print on demand from anywhere, with access to files on the go.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Efficiency:&lt;/strong&gt; Streamline workflows with admin controls, secure online access, and approval processes.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Customization:&lt;/strong&gt; Scalable solutions tailored to business needs, including large-scale projects.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Sustainability:&lt;/strong&gt; Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.&lt;/p&gt;
    &lt;/div&gt;</div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main"><div class="custom_block_online_button" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="MW4OL4S"><div role="button" class="pagebuilder-button-link" aria-label="" data-testid="" data-element="empty_link" data-pb-style="XEQPKHO"><span aria-label="" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div><div data-content-type="tab-item" data-appearance="default" data-tab-name="FedEx® OnCampus" data-background-images="{}" data-element="main" id="HYCR905" data-pb-style="IHKCP3K"><div data-content-type="html" data-appearance="default" data-element="main">&lt;h3 class="elevate_workflow_title"&gt;Elevate your workflow with our online platform&lt;/h3&gt;
    &lt;div class="elevate_workflow_content"&gt;
    &lt;p&gt;&lt;strong&gt;. Convenience:&lt;/strong&gt; Print on demand from anywhere, with access to files on the go.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Efficiency:&lt;/strong&gt; Streamline workflows with admin controls, secure online access, and approval processes.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Customization:&lt;/strong&gt; Scalable solutions tailored to business needs, including large-scale projects.&lt;/p&gt;
    &lt;p&gt;&lt;strong&gt;. Sustainability:&lt;/strong&gt; Green printing options with recycled paper and soy-based inks, reducing waste and energy consumption.&lt;/p&gt;
    &lt;/div&gt;</div><div data-content-type="buttons" data-appearance="inline" data-same-width="false" data-element="main"><div class="custom_block_online_button" data-content-type="button-item" data-appearance="default" data-element="main" data-pb-style="WMD6H0V"><div role="button" class="pagebuilder-button-link" aria-label="" data-testid="" data-element="empty_link" data-pb-style="NPC757D"><span aria-label="" data-testid="" data-element="link_text">LEARN MORE</span></div></div></div></div></div></div></div></div></div></div></div><div id="%identifier%" data-content-type="row" data-appearance="contained" data-element="main"><div data-enable-parallax="0" data-parallax-speed="0.5" data-background-images="{}" data-background-type="image" data-video-loop="true" data-video-play-only-visible="true" data-video-lazy-load="true" data-video-fallback-src="" data-element="inner" data-pb-style="Q6VCSL0"><div data-content-type="block" data-appearance="default" aria-label="" data-element="main">{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="'.$kalturaBlockId.'" type_name="CMS Static Block"}}</div></div></div>',
                'created_for' => 'any',
                'preview_image' => "$this->staticPath/thirdpartysubcategorypage609d1edc77a15.jpg"
            ];
    
            try {
                $connection->insert($tableName, $templateData);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Error inserting data: %1', $e->getMessage())); // Updated exception
            }
        }
        
        $this->moduleDataSetup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return ['marketing_content_tabbed_content'];
    }
}
