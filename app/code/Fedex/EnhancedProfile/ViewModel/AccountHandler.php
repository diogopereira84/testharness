<?php
/**
 * Copyright © FedEX, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\EnhancedProfile\ViewModel;

use Fedex\EnhancedProfile\Helper\Account;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * AccountHandler ViewModel class
 */
class AccountHandler implements ArgumentInterface
{
    /**
     * @param Session $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Account $accountHelper
     */
    public function __construct(
        protected Session $customerSession,
        protected CheckoutSession $checkoutSession,
        protected Account $accountHelper
    )
    {
    }

    /**
     * @param bool|string $filterByType
     * @return array
     */
    public function getActivePersonalAccountList($filterByType = false): array
    {
        return $this->accountHelper->getActivePersonalAccountList($filterByType);
    }

    /**
     * @param $filterByType
     * @return array
     */
    public function getActiveCompanyAccountList($filterByType = false): array
    {
        return $this->accountHelper->getActiveCompanyAccountList($filterByType);
    }

    /**
     * @return string|null
     */
    public function getCompanyName()
    {
        $customerId = $this->customerSession->getCustomerId();
        $company = $this->accountHelper->getCompanyByCustomerId($customerId);
        return $company && $company->getCompanyName() ? $company->getCompanyName() :  'Site';
    }

    /**
     * @return string|null
     */
    public function getAppliedFedexAccDiscountOnly()
    {
        return $this->checkoutSession->getAppliedFedexAccDiscountOnly();
    }

    /**
     * @return string|int
     */
    public function decideAccountToSelect($companyAccountList, $personalAccountList)
    {
        foreach ($companyAccountList as $accountNumber => $accountData) {
            return $accountNumber;
        }
        $personalDefaultAccount = array_search(
            1,
            array_column($personalAccountList, 'selected', 'account_number')
        );
        if ($personalDefaultAccount) {
            return $personalDefaultAccount;
        }

        foreach ($personalAccountList as $accountNumber => $accountData) {
            return $accountNumber;
        }

        return false;
    }
}
