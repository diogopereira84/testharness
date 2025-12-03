<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\ThirdPartyLegalDisclaimer\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Admin Values Block Class
 *
 */
class AdminValues extends Template
{
    private const THIRD_PARTY_MODAL_TOGGLE_PATH = 'environment_toggle_configuration/environment_toggle/sgc_b1411774_pass_third_party_legal_disclaimer_data';
    private const XML_PATH_THIRD_PARTY_MODAL_TITLE = 'web/third_party_modal/third_party_modal_title';
    private const XML_PATH_THIRD_PARTY_MODAL_TOP_DESCRIPTION = 'web/third_party_modal/third_party_modal_top_description';
    private const XML_PATH_THIRD_PARTY_MODAL_BOTTOM_DESCRIPTION = 'web/third_party_modal/third_party_modal_bottom_description';
    private const ALLOWED_EDITOR_HTML_TAGS = ['<strong>', '<em>', '<span>', '<a>'];

    /**
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfigInterface,
        protected ToggleConfig $toggleConfig,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Third Party Legal Disclaimer Modal Toggle
     * 
     * @return bool
     */
    public function getThirdPartyModalAdminDataToggle()
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::THIRD_PARTY_MODAL_TOGGLE_PATH);
    }

    /**
     * @return string
     */
    public function getThirdPartyModalTitle()
    {
        $thirdPartyModalTitle = $this->scopeConfigInterface->getValue(self::XML_PATH_THIRD_PARTY_MODAL_TITLE) ?? '';

        return strip_tags($thirdPartyModalTitle, self::ALLOWED_EDITOR_HTML_TAGS);
    }

    /**
     * @return string
     */
    public function getThirdPartyModalTopDescription()
    {
        $thirdPartyModalTopDescription = $this->scopeConfigInterface->getValue(self::XML_PATH_THIRD_PARTY_MODAL_TOP_DESCRIPTION) ?? '';

        return strip_tags($thirdPartyModalTopDescription, self::ALLOWED_EDITOR_HTML_TAGS);
    }

    /**
     * @return string
     */
    public function getThirdPartyModalBottomDescription()
    {
        $thirdPartyModalBottomDescription = $this->scopeConfigInterface->getValue(self::XML_PATH_THIRD_PARTY_MODAL_BOTTOM_DESCRIPTION) ?? '';

        return strip_tags($thirdPartyModalBottomDescription, self::ALLOWED_EDITOR_HTML_TAGS);
    }
}