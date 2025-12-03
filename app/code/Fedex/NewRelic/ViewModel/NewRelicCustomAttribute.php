<?php
declare(strict_types=1);

namespace Fedex\NewRelic\ViewModel;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Fedex\Delivery\Helper\Data;
use Magento\Company\Model\CompanyRepository;
use Magento\Customer\Model\Session;
use Fedex\Base\Helper\Auth as AuthHelper;

class NewRelicCustomAttribute implements ArgumentInterface
{
    /**
     * Constructor Initialization
     *
     * @param Session $customerSession
     * @param Data $helper
     * @param CompanyRepository $companyRepository
     * @param AuthHelper $authHelper
     */
    public function __construct(
        private Session      $customerSession,
        private Data $helper,
        private CompanyRepository $companyRepository,
        protected AuthHelper $authHelper
    )
    {
    }

    /**
     * Check customer is epro
     *
     * @return bool
     */
    public function isEproCustomer()
    {
        $isCommercialCustomer = $this->helper->isCommercialCustomer();
        return $isCommercialCustomer;
    }

    /**
     * GetAssigned company Value
     *
     * @return mixed|string|null
     * @throws NoSuchEntityException
     */
    public function getAssignedCompany()
    {
        $companyName = '';
        $isCommercialCustomer = $this->helper->isCommercialCustomer();
        if ($isCommercialCustomer) {
            $companyId = $this->customerSession->getCustomerCompany();
            if ($companyId) {
                $company = $this->companyRepository->get($companyId);
                $companyName = $company->getCompanyName();
            }
        }
        return $companyName;
    }


    /**
     * Check customer is loggedIN
     * @deprecated use \Fedex\Base\Helper\Auth::isLoggedIn() instead
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return $this->authHelper->isLoggedIn();
    }
}
