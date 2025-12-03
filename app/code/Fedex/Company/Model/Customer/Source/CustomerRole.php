<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Customer\Source;

use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Option Source for customer role.
 *
 * @codeCoverageIgnore
 */
class CustomerRole implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getOptions() as $value => $label) {
            $options[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        return $options;
    }

    /**
     * Get option label by value.
     *
     * @param int $value
     * @return string|null
     */
    public function getLabel($value)
    {
        $options = $this->getOptions();

        return $options[$value] ?? null;
    }

    /**
     * Get customer type options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            CompanyCustomerInterface::TYPE_COMPANY_ADMIN => __('Admin'),
            CompanyCustomerInterface::TYPE_COMPANY_USER => __('Default User'),
            CompanyCustomerInterface::TYPE_INDIVIDUAL_USER => __('Default User'),
        ];
    }
}
