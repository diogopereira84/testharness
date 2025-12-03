<?php
/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Shipto\Plugin\Checkout;

use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Plugin Class for Layout Processing
 */
class LayoutProcessorPlugin
{

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected \Magento\Customer\Model\Session $customerSession,
        protected CompanyRepositoryInterface $companyRepository,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Plugin function
     *
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(
        $subject,
        array $jsLayout
    ) {

        $shipTo = 0;
        $isLoggedInUser = $this->customerSession->isLoggedIn();
        $companyId = $this->customerSession->getCustomerCompany();
        $isEproLogin = false;
        if ($companyId != null && $companyId > 0) {
            $customerRepo = $this->companyRepository->get((int) $companyId);
            $companyLoginType = $customerRepo->getStorefrontLoginMethodOption();
            if ($companyLoginType == 'commercial_store_epro') {
                $shipTo = $customerRepo->getRecipientAddressFromPo();
                $isEproLogin = true;
            }
        }
        if ($shipTo) {
            /**** remove required from firstname *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['firstname']['validation'] = ['required-entry' => false];

            /**** hide firstname *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['firstname']['config']['additionalClasses'] = "hide shipto-firstname";

            /**** remove required from lastname *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['lastname']['validation'] = ['required-entry' => false];

            /**** hide lastname *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['lastname']['config']['additionalClasses'] = "hide shipto-lastname";

            /**** remove required from city *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['city']['validation'] = ['required-entry' => false];

            /**** hide city *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['city']['config']['additionalClasses'] = "hide shipto-city";

            /**** remove required from street *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['street']['required'] = false;

            /**** hide street *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['street']['config']['additionalClasses'] = "hide shipto-street";

            /**** remove required from streetfirstinput *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['street']['children'][0]['validation'] = ['required-entry' => false];

            /**** remove required from telephone *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['telephone']['validation'] = ['required-entry' => false];

            /**** hide telephone *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['telephone']['config']['additionalClasses'] = "hide shipto-telephone";

            /**** remove required from regionid *****/
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['region_id']['validation'] = ['required-entry' => true];

            /**** hide regionid *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['region_id']['config']['additionalClasses'] = "hide shipto-region-id";

            /**** hide countryid *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['country_id']['config']['additionalClasses'] = "hide shipto-country-id";

            /**** hide company *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['company']['config']['additionalClasses'] = "hide shipto-company";

            /**** tooltip add zipcode *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['postcode']['tooltip']['description'] =
                "Please include recipient location  by entering  State  and ZIP code  for this order.
            The complete delivery address  is what  is provided on Purchase Order(PO).";

            /**** add Class regionid *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['region_id']['config']['additionalClasses'] = "shipto-region";

            /**** add Class postcode *****/

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['postcode']['config']['additionalClasses'] = "shipto-postcode";

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['street']['component'] = 'Fedex_Shipto/js/form/components/group';
        } else {
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['company']
            ['validation']['fedex-validate-company'] = true;

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['company']
            ['validation']['fedex-validate-company-special-characters'] = true;
            if ($this->toggleConfig->getToggleConfigValue('explorers_d_193257_fix')) {
                $jsLayout['components']['checkout']['children']['steps']
                ['children']['shipping-step']['children']['shippingAddress']
                ['children']['shipping-address-fieldset']['children']['street']['children'][0]
                ['validation'] = ['fedex-validate-street' => true,
                'validate-input-address-special-characters' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street'];
                $jsLayout['components']['checkout']['children']['steps']
                ['children']['shipping-step']['children']['shippingAddress']
                ['children']['shipping-address-fieldset']['children']['street']['children'][1]
                ['validation'] = ['fedex-validate-street' => true,
                'validate-input-address-special-characters' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street'];
            } else {
                $jsLayout['components']['checkout']['children']['steps']
                ['children']['shipping-step']['children']['shippingAddress']
                ['children']['shipping-address-fieldset']['children']['street']['children'][0]
                ['validation']['fedex-validate-street'] = true;
                $jsLayout['components']['checkout']['children']['steps']
                ['children']['shipping-step']['children']['shippingAddress']
                ['children']['shipping-address-fieldset']['children']['street']['children'][1]
                ['validation']['fedex-validate-street'] = true;
            }
        }

        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['street']['children'][0]
            ['validation']['fedex-validate-not-number'] = true;

        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['email_id']
            ['validation']['fedex-validate-email'] = true;

        $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['country_id']
            ['disabled'] = true;

        if ($this->toggleConfig->getToggleConfigValue('explorers_d_193257_fix')) {
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['firstname']
            ['validation'] = ['required-entry' => true, 'validate-input-name' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname',
                'validate-input-name-special-characters' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname'];

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['lastname']
            ['validation'] = ['required-entry' => true, 'validate-input-name' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.lastname',
                'validate-input-name-special-characters' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.lastname'];
        } else {
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['firstname']
            ['validation'] = ['required-entry' => true, 'validate-input-name' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname'];

            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']
            ['shipping-address-fieldset']['children']['lastname']
            ['validation'] = ['required-entry' => true, 'validate-input-name' => 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.lastname'];
        }

        if (!$shipTo) {
            $customAttributeCode = 'residence_shipping';
            $fieldConfiguration = [
                'component' => 'Magento_Ui/js/form/element/single-checkbox',
                'config' => [
                    'customScope' => 'shippingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/checkbox',
                ],
                'dataScope' => 'shippingAddress.custom_attributes.residence_shipping',
                'description' => __('I\'m shipping to a residence (optional)'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [],
                'sortOrder' => 130,
                'value' => $this->toggleConfig->getToggleConfigValue('tech_titans_d217174') ? 0 : 1
            ];

            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $fieldConfiguration;
        }
        if ($this->toggleConfig->getToggleConfigValue('explorers_e_450676_personal_address_book') && $isLoggedInUser && !$isEproLogin) {
            $customAttributeCode = 'save_addressbook';
            $fieldConfiguration = [
                'component' => 'Magento_Ui/js/form/element/single-checkbox',
                'config' => [
                    'customScope' => 'shippingAddress',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/checkbox',
                ],
                'dataScope' => 'shippingAddress.custom_attributes.save_addressbook',
                'description' => __('Save to Address Book'),
                'provider' => 'checkoutProvider',
                'visible' => true,
                'validation' => [],
                'sortOrder' => 131,
                'value' => 0
            ];
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $fieldConfiguration;
        }

        $jsLayout = $this->addingCommonClassToAddress($jsLayout);
        if ($this->toggleConfig->getToggleConfigValue('maegeeks_pobox_toggle')) {
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['street']['children'][0]
            ['placeholder'] = "Street (No PO boxes)";
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']
            ['children']['shipping-address-fieldset']['children']['street']['children'][1]
            ['placeholder'] = "Apartment, suite, etc. (No PO boxes)";
      }
        return $jsLayout;
    }

    /**
     * @param $jsLayout
     * @return array
     */
    protected function addingCommonClassToAddress($jsLayout)
    {
        if ($this->toggleConfig->getToggleConfigValue('techtitans_d_203785_address_line_validation')){
            $jsLayout['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['street']['children'][0]['validation']['required-entry'] = true;
        }

        $currentStreet0Class = $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['children'][0]['config']['additionalClasses'] ?? '';

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['children'][0]['config']['additionalClasses'] = $currentStreet0Class . "additional address-field";

        $currentStreet1Class = $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['children'][0]['config']['additionalClasses'] ?? '';

        $jsLayout['components']['checkout']['children']['steps']
        ['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']
        ['children']['street']['children'][1]['config']['additionalClasses'] = $currentStreet1Class . "additional address-field";

        return $jsLayout;
    }
}
