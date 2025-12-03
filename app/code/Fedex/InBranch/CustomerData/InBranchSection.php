<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\InBranch\CustomerData;

use Fedex\InBranch\Model\InBranchValidation;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\InBranch\Helper\Data;

class InBranchSection implements SectionSourceInterface
{
    /**
     * @param InBranchValidation $inbranchvalidation
     * @param Data $data
     */
    public function __construct(
        private InBranchValidation $inbranchvalidation,
        private Data $data,
    ) {
    }

    /**
     * Add InBranch Data in section
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSectionData()
    {
        return [
            'isInBranchDataInCart' => $this->data->checkInCartINBranch(),
            'isInBranchUser'=> (bool) $this->inbranchvalidation->isInBranchUser()
        ];
    }
}
