<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class LoginOptions implements \Magento\Framework\Option\ArrayInterface
{
    
    public const ADD_NEW_COMPANY_REQUIRED_FIELDS = 'environment_toggle_configuration/environment_toggle/techtitans_208740_add_new_company';
    /**
     * Initialize dependencies.
     *
     * @param ToggleConfig $toggleConfig
     */

    public function __construct(
        protected ToggleConfig $toggleConfig
    )
    {
    }
    /**
     * {inheritdoc}
     */
    public function toOptionArray()
    {
        //D-208740
    $addNewCompanyRequiredFields = $this->toggleConfig->getToggleConfig(self::ADD_NEW_COMPANY_REQUIRED_FIELDS);
    // Initialize the login options array
    $loginOptions = [];

    if ($addNewCompanyRequiredFields) {
        $loginOptions = [
            ['value' => 'commercial_store_wlgn', 'label' => __('FCL')],
            ['value' => 'commercial_store_sso', 'label' => __('SSO')],
            ['value' => 'commercial_store_epro', 'label' => __('EPro Punchout')],
        ];
    }
    else {
        $loginOptions = [
            ['value' => '', 'label' => __('Select Login Options')],
            ['value' => 'commercial_store_wlgn', 'label' => __('FCL')],
            ['value' => 'commercial_store_sso', 'label' => __('SSO')],
            ['value' => 'commercial_store_epro', 'label' => __('EPro Punchout')],
        ];
    }
        if ($this->toggleConfig->getToggleConfigValue('xmen_enable_sso_group_authentication_method')) {
            $loginOptions[] = ['value' => 'commercial_store_sso_with_fcl', 'label' => __('SSO with FCL User')];
        }

        return $loginOptions;
    }
}