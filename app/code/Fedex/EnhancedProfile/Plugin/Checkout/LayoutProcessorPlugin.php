<?php
namespace Fedex\EnhancedProfile\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;

class LayoutProcessorPlugin
{
    const TIGER_E486666 = 'tiger_e486666';
    const SHIPPING_ACCOUNT_BOX_TITLE_GUEST = 'enhanced_profile_group/shipping_account/box_title_guest';
    const SHIPPING_ACCOUNT_BOX_TITLE_CUSTOMER = 'enhanced_profile_group/shipping_account/box_title_customer';
    const SHIPPING_ACCOUNT_BOX_DESCRIPTION_GUEST = 'enhanced_profile_group/shipping_account/box_description_guest';
    const SHIPPING_ACCOUNT_BOX_DESCRIPTION_CUSTOMER = 'enhanced_profile_group/shipping_account/box_description_customer';

    /**
     * LayoutProcessorPlugin Constructor
     *
     * @param ToggleConfig $toggleConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerSession $customerSession
     */
    public function __construct(
        protected ToggleConfig $toggleConfig,
        protected ScopeConfigInterface $scopeConfig,
        protected CustomerSession $customerSession,
    ){
    }

    /**
     * After plugin for process method in LayoutProcessor class.
     *
     * @param LayoutProcessor $subject
     * @param array $result
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, array $result, $jsLayout)
    {
        if ($this->isTigerE486666Enabled()) {
            $result['components']['checkout']['children']['steps']
            ['children']['shipping-step']['children']['shippingAddress']['children']
            ['fedex-shipping-account'] = $this->getLayoutConfigForFedexShippingAccountField();
        }

        return $result;
    }

    private function getLayoutConfigForFedexShippingAccountField(): array
    {
        return [
            'component' => 'uiComponent',
            'displayArea' => 'fedex-shipping-account-fieldset',
            'children' => [
                'fedex-shipping-account' => [
                    'component' => 'Fedex_EnhancedProfile/js/view/checkout/shipping_step/fedex-shipping-account',
                    'config' => [
                        'shippingAccountBoxTitle' => __($this->getFedexShippingAccountBoxTitle()),
                        'shippingAccountBoxDescription' => __($this->getFedexShippingAccountBoxDescription()),
                        'toggleTigerE486666' => $this->isTigerE486666Enabled(),
                    ],
                    'children' => [
                        'shipping-account-acknowledgement' => [
                            'component' => 'Fedex_MarketplaceCheckout/js/view/checkout/shipping-account-acknowledgement',
                            'displayArea' => 'shipping-account-acknowledgement'
                        ],
                    ],
                ]
            ]
        ];
    }

    private function isTigerE486666Enabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_E486666);
    }

    private function getFedexShippingAccountBoxTitle(): string
    {
        if($this->customerSession->isLoggedIn()) {
            return $this->scopeConfig->getValue(self::SHIPPING_ACCOUNT_BOX_TITLE_CUSTOMER) ?? '';
        }

        return $this->scopeConfig->getValue(self::SHIPPING_ACCOUNT_BOX_TITLE_GUEST) ?? '';
    }

    private function getFedexShippingAccountBoxDescription(): string
    {
        if($this->customerSession->isLoggedIn()) {
            return $this->scopeConfig->getValue(self::SHIPPING_ACCOUNT_BOX_DESCRIPTION_CUSTOMER) ?? '';
        }

        return $this->scopeConfig->getValue(self::SHIPPING_ACCOUNT_BOX_DESCRIPTION_GUEST) ?? '';
    }
}
