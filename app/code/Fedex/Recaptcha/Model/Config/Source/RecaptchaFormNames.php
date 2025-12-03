<?php
declare(strict_types=1);
namespace Fedex\Recaptcha\Model\Config\Source;

class RecaptchaFormNames implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'checkout_order', 'label' => __('Checkout Submit Order')],
            ['value' => 'checkout_cc', 'label' => __('Checkout Save CC')],
            ['value' => 'profile_cc', 'label' => __('Profile CC')],
            ['value' => 'shared_cc', 'label' => __('Shared Credit Cards')],
            ['value' => 'checkout_fedex_account', 'label' => __('Checkout Save Fedex Account')],
            ['value' => 'profile_fedex_account', 'label' => __('Profile Fedex Account')],
            ['value' => 'checkout_shipping_account_validation', 'label' => __('Checkout Validate Fedex Shipping Account')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'checkout_order' => __('Checkout Submit Order'),
            'checkout_cc' => __('Checkout Save CC'),
            'profile_cc' => __('Profile CC'),
            'shared_cc' => __('Shared Credit Cards'),
            'checkout_fedex_account' => __('Checkout Save Fedex Account'),
            'profile_fedex_account' => __('Profile Fedex Account'),
            'checkout_shipping_account_validation' => __('Checkout Validate Fedex Shipping Account'),
        ];
    }
}
