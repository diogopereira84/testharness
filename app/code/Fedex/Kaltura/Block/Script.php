<?php
declare(strict_types=1);

/**
 * @category Fedex
 * @package Fedex_Kaltura
 * @copyright (c) 2022.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
namespace Fedex\Kaltura\Block;


use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Script extends Template
{
    const XML_PATH_ENABLED_KALTURA = 'cms/kaltura/enabled';
    const XML_PATH_KALTURA_SCRIPT = 'cms/kaltura/head_script';
    const XML_PATH_KALTURA_PARTNER_ID = 'cms/kaltura/partner_id';
    const XML_PATH_KALTURA_UI_CONFIG_ID = 'cms/kaltura/ui_config_id';

    /** @var string */
    protected $_template = 'Fedex_Kaltura::script.phtml';

    /**
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private SecureHtmlRenderer $secureHtmlRenderer,
        array $data = []
    ) {
        $this->secureHtmlRenderer = $secureHtmlRenderer;
        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        if($this->isKalturaEnabled()) {
            return parent::_toHtml();
        }
    }

    /**
     * @return bool
     */
    public function isKalturaEnabled()
    {
        return $this->_scopeConfig->isSetFlag(self::XML_PATH_ENABLED_KALTURA);
    }

    /**
     * @return mixed
     */
    public function getScript()
    {
        $script = $this->_scopeConfig->getValue(self::XML_PATH_KALTURA_SCRIPT);
        if($script && strpos($script, 'src=') !== false) {
            preg_match('/<script\s+src="([^"]+)"/', $script, $matches);
            if (!empty($matches[1])) {
                return $this->secureHtmlRenderer->renderTag(
                    'script',
                    [
                        'type'  => 'text/javascript',
                        'src'   => $matches[1],
                        'id'  => 'kaltura-script',
                    ],
                    ' ',
                    false
                );
            }
        }
        return $script;
    }

    /**
     * @return mixed
     */
    public function getPartnerId()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_KALTURA_PARTNER_ID);
    }

    /**
     * @return mixed
     */
    public function getUiConfigId()
    {
        return $this->_scopeConfig->getValue(self::XML_PATH_KALTURA_UI_CONFIG_ID);
    }
}
