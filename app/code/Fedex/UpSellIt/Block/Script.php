<?php
declare(strict_types=1);

/**
 * @category Fedex
 * @package Fedex_UpSellIt
 * @copyright (c) 2022.
 * @author Austin King austin.king@fedex.com
 */
namespace Fedex\UpSellIt\Block;


use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Script extends Template
{
    const XML_PATH_ACTIVE_UPSELLIT = 'web/upsellit/upsellit_active';
    const XML_PATH_UPSELLIT_SCRIPT = 'web/upsellit/upsellit_script';

    /**
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param SecureHtmlRenderer $secureHtmlRenderer
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfigInterface,
        private SecureHtmlRenderer $secureHtmlRenderer,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function _toHtml()
    {
        if ($this->scopeConfigInterface->isSetFlag(self::XML_PATH_ACTIVE_UPSELLIT)) {
            return parent::_toHtml();
        }
    }

    /**
     * @return mixed
     */
    public function getScript()
    {
        $upsellitScript = $this->scopeConfigInterface->getValue(self::XML_PATH_UPSELLIT_SCRIPT);
        $pattern = '/<script\b[^>]*>(.*?)<\/script>/is';
        preg_match_all($pattern, $upsellitScript, $matches);
        foreach ($matches[1] as $scriptWithoutTag) {
            $upsellitScript = $this->buildRenderTag($scriptWithoutTag);
        }
        return $upsellitScript;
    }

    /**
     * @param $content
     * @return string
     */
    private function buildRenderTag($content)
    {
        return $this->secureHtmlRenderer->renderTag(
            'script',
            ['type' => 'text/javascript'],
            $content,
            false
        );
    }
}
