<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;

class Years implements OptionSourceInterface
{
    /**
     * Years constructor
     *
     * @param DateTimeFactory $dateTimeFactory
     * @return void
     */
    public function __construct(
        protected DateTimeFactory $dateTimeFactory
    )
    {
    }

    /**
     * Get list of months
     *
     * @return array
     */
    public function toOptionArray()
    {
        $dateModel = $this->dateTimeFactory->create();
        $currentYear = $dateModel->gmtDate('Y');

        $yearsList = [
            ['value' => '', 'label' => __('Year')],
        ];
        for ($i = 1; $i <= 11; $i++) {
            $yearsList[] = ['value' => $currentYear, 'label' => __($currentYear)];
            $timeStamp = $dateModel->timestamp("+" . $i . " years");
            $currentYear = $dateModel->gmtDate('Y', $timeStamp);
        }

        return $yearsList;
    }
}
