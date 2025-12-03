<?php

namespace Fedex\SelfReg\Ui\Component\Form\Users;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;




class Site implements OptionSourceInterface
{
    /**
     * @param RequestInterface $request
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        protected RequestInterface $request,
        protected CompanyRepositoryInterface $companyRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return $this->getCompanyData();
    }

    public function getCompanyData() {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $companyData = $this->companyRepository->getList($searchCriteria)->getItems();
        
        $companyInfo = [];
        foreach($companyData as $company) {
            $companyInfo[] = [
                'value'   => $company->getId(),
                'label' => $company->getCompanyName(),
            ];
        }
        return $companyInfo;
    }
}
