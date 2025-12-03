<?php
declare(strict_types=1);

namespace Fedex\Recaptcha\Test\Unit\Model\Config\Source;

use Fedex\Recaptcha\Model\Config\Source\RecaptchaFormNames;
use PHPUnit\Framework\TestCase;

class RecaptchaFormNamesTest extends TestCase
{
    private $recaptchaFormNames;

    protected function setUp(): void
    {
        $this->recaptchaFormNames = new RecaptchaFormNames();
    }

    public function testToOptionArray()
    {
        $expected = [
            ['value' => 'checkout_order', 'label' => __('Checkout Submit Order')],
            ['value' => 'checkout_cc', 'label' => __('Checkout Save CC')],
            ['value' => 'profile_cc', 'label' => __('Profile CC')],
            ['value' => 'shared_cc', 'label' => __('Shared Credit Cards')],
            ['value' => 'checkout_fedex_account', 'label' => __('Checkout Save Fedex Account')],
            ['value' => 'profile_fedex_account', 'label' => __('Profile Fedex Account')],
            ['value' => 'checkout_shipping_account_validation', 'label' => __('Checkout Validate Fedex Shipping Account')],
        ];

        $this->assertEquals($expected, $this->recaptchaFormNames->toOptionArray());
    }

    public function testToArray()
    {
        $expected = [
            'checkout_order' => __('Checkout Submit Order'),
            'checkout_cc' => __('Checkout Save CC'),
            'profile_cc' => __('Profile CC'),
            'shared_cc' => __('Shared Credit Cards'),
            'checkout_fedex_account' => __('Checkout Save Fedex Account'),
            'profile_fedex_account' => __('Profile Fedex Account'),
            'checkout_shipping_account_validation' => __('Checkout Validate Fedex Shipping Account'),
        ];

        $this->assertEquals($expected, $this->recaptchaFormNames->toArray());
    }
}
