<?php
/**
 * @category Fedex
 * @package FedexRate
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Model\Config\Source;

class LoginType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '0', 'label' => __('Select')],
            ['value' => '1', 'label' => __('WLGN')],
            ['value' => '2', 'label' => __('Customer SSO')]
        ];
    }
}
