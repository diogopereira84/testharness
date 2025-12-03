<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model\Config\Source;

use Magento\Company\Model\Company;
use Magento\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class Companies implements OptionSourceInterface
{
    /**
     * @param CompanyCollectionFactory $companyCollectionFactory
     */
    public function __construct(
        protected CompanyCollectionFactory $companyCollectionFactory
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
        $companyCollection = $this->companyCollectionFactory->create();
        $companyList = [['value' => '', 'label' => __('Select Company')]];
        /** @var Company $company */
        foreach ($companyCollection as $company) {
            $companyList[] = [
                'value' => $company->getId(),
                'label' => $company->getCompanyName()
            ];
        }
        return $companyList;
    }
}
