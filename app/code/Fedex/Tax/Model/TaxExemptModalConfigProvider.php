<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Tax\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class TaxExemptModalConfigProvider implements ConfigProviderInterface
{
    private const TAX_EXEMPT_TOGGLE_PATH =
    'environment_toggle_configuration/environment_toggle/sgc_b1392314_pass_tax_exempt_modal_data';
    private const XML_PATH_TAX_EXEMPT_TITLE = 'web/tax_exempt/tax_exempt_title';
    private const XML_PATH_TAX_EXEMPT_BODY = 'web/tax_exempt/tax_exempt_body';
    private const XML_PATH_TAX_EXEMPT_PRIMARY_CTA = 'web/tax_exempt/tax_exempt_primary_cta';
    private const XML_PATH_TAX_EXEMPT_SECONDARY_CTA = 'web/tax_exempt/tax_exempt_secondary_cta';
    private const XML_PATH_TAX_EXEMPT_FOOTER_TEXT = 'web/tax_exempt/tax_exempt_footer_text';
    private const ALLOWED_EDITOR_HTML_TAGS = ['<strong>', '<em>', '<span>', '<a>'];

    /**
     * TaxExemptModalConfigProvider Constructor.
     *
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfigInterface,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Tax Exempt Modal configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'is_tax_exempt_modal_admin_data' => $this->getTaxExemptModalAdminDataToggle(),
            'tax_exempt_modal_title' => $this->getTaxExemptModalTitle(),
            'tax_exempt_modal_body' => $this->getTaxExemptModalBody(),
            'tax_exempt_modal_primary_cta' => $this->getTaxExemptModalPrimaryCTA(),
            'tax_exempt_modal_secondary_cta' => $this->getTaxExemptModalSecondaryCTA(),
            'tax_exempt_modal_footer' => $this->getTaxExemptModalFooter()
        ];
    }

    /**
     * Get Tax Exempt Modal Toggle
     *
     * @return bool
     */
    public function getTaxExemptModalAdminDataToggle()
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::TAX_EXEMPT_TOGGLE_PATH);
    }

    /**
     * Get Tax Exempt Modal Title
     *
     * @return string
     */
    public function getTaxExemptModalTitle()
    {
        $taxExemptModalTitle = $this->scopeConfigInterface->getValue(self::XML_PATH_TAX_EXEMPT_TITLE) ?? '';

        return strip_tags($taxExemptModalTitle, self::ALLOWED_EDITOR_HTML_TAGS);
    }

    /**
     * Get Tax Exempt Modal Body
     *
     * @return string
     */
    public function getTaxExemptModalBody()
    {
        $taxExemptModalBody = $this->scopeConfigInterface->getValue(self::XML_PATH_TAX_EXEMPT_BODY) ?? '';

        return strip_tags($taxExemptModalBody, self::ALLOWED_EDITOR_HTML_TAGS);
    }

    /**
     * Get Tax Exempt Modal Primary CTA
     *
     * @return string
     */
    public function getTaxExemptModalPrimaryCTA()
    {
        $taxExemptModalPrimaryCTA = $this->scopeConfigInterface->getValue(self::XML_PATH_TAX_EXEMPT_PRIMARY_CTA) ?? '';

        return strip_tags($taxExemptModalPrimaryCTA, self::ALLOWED_EDITOR_HTML_TAGS);
    }

    /**
     * Get Tax Exempt Modal Secondary CTA
     *
     * @return string
     */
    public function getTaxExemptModalSecondaryCTA()
    {
        $taxExemptModalSecondaryCTA =
        $this->scopeConfigInterface->getValue(self::XML_PATH_TAX_EXEMPT_SECONDARY_CTA) ?? '';

        return strip_tags($taxExemptModalSecondaryCTA, self::ALLOWED_EDITOR_HTML_TAGS);
    }

    /**
     * Get Tax Exempt Modal Footer
     *
     * @return string
     */
    public function getTaxExemptModalFooter()
    {
        $taxExemptModalFooter = $this->scopeConfigInterface->getValue(self::XML_PATH_TAX_EXEMPT_FOOTER_TEXT) ?? '';

        return strip_tags($taxExemptModalFooter, self::ALLOWED_EDITOR_HTML_TAGS);
    }
}
