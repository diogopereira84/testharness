<?php
namespace Fedex\Customer\Model\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class CustomOptions extends AbstractSource
{
    public function getAllOptions()
    {
        return [
            ['value' => 0, 'label' => __('Inactive')],
            ['value' => 1, 'label' => __('Active')],
            ['value' => 2, 'label' => __('Pending For Approval')],
            ['value' => 3, 'label' => __('Email Verification Pending')],
        ];
    }
}