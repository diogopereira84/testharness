<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Plugin\Adminhtml\Controller\Index;

use Closure;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Company\Controller\Adminhtml\Index\Edit as CompanyEdit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;

class Edit
{
    /**
     * Edit constructor
     *
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param CompanyHelper $companyHelper
     * @return void
     */
    public function __construct(
        private RequestInterface $request,
        private ManagerInterface $messageManager,
        private CompanyHelper $companyHelper
    )
    {
    }

    /**
     * Show warning message when credit card token has expired
     * B-1359540 : For Credit card configured in Magento Admin , expiration date should be validated
     *
     * @param CompanyEdit $subject
     * @param Closure $proceed
     * @return CompanyEdit
     */
    public function aroundExecute(CompanyEdit $subject, Closure $proceed)
    {
        if ($companyId = $this->request->getParam('id')) {
            $creditCardTokenExpiryDate = $this->companyHelper->getCreditCardTokenExpiryDateTime($companyId);
            if ($creditCardTokenExpiryDate &&
                !$this->companyHelper->isValidCreditCardTokenExpiryDate($creditCardTokenExpiryDate)) {
                $this->messageManager->addWarningMessage(
                    __('Your saved credit card has expired. Please update the credit card information.')
                );
            }
        }

        return $proceed();
    }
}
