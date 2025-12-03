<?php
/**
 * @category  Fedex
 * @package   Fedex_Catalog
 * @author   Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\Catalog\Plugin;

use Fedex\Catalog\Model\Config;
use Magento\Framework\DataObject;
use Magento\PageBuilder\Model\Wysiwyg\DefaultConfigProvider;

class ToolbarConfigProviderPlugin
{
    public function __construct(
        private readonly Config $catalogConfig
    ) {
    }
    /**
     * After plugin for getConfig method
     *
     * @param DefaultConfigProvider $subject
     * @param DataObject $config
     * @return DataObject
     */
    public function afterGetConfig(
        DefaultConfigProvider $subject,
        DataObject  $config
    ): DataObject
    {
        $tinymce = $config->getData('tinymce');
        $tinymce['toolbar'] = $this->catalogConfig->wysiwygToolbarConfig();
        $config->setData('tinymce',$tinymce);
        return $config;
    }
}
