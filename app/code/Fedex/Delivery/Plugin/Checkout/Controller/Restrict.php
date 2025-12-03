<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Plugin\Checkout\Controller;

use Magento\Checkout\Controller\Index\Index;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlFactory;

/**
 * Restrict Model Plugin
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Restrict
{
    /**
     * @var \Magento\Framework\UrlFactory $urlFactory
     */
    private $urlModel;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @param UrlFactory $urlFactory
     * @param RedirectFactory $redirectFactory
     * @param ManagerInterface $messageManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     *
     */
    public function __construct(
        UrlFactory $urlFactory,
        RedirectFactory $redirectFactory,
        private ManagerInterface $messageManager,
        private \Magento\Customer\Model\Session $customerSession,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private CompanyRepositoryInterface $companyRepository
    ) {

        $this->urlModel = $urlFactory;
        $this->resultRedirectFactory = $redirectFactory;
    }

    /**
     * Save Shipping Address
     *
     * @param object $subject
     * @param object $proceed
     * @return  object|string  proceed()|url 404
     */
    public function aroundExecute(
        Index $subject,
        \Closure $proceed
    ) {

        $this->urlModel = $this->urlModel->create();
        $id = $this->customerSession->getCustomer()->getId();
        if ($id) {
            $customer = $this->customerRepository->getById($id);
            $this->customerSession->getCustomer();
            $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
            if ($companyAttributes) {
                $companyId = $companyAttributes->getCompanyId();
                if ($companyId) {
                    $company = $this->companyRepository->get((int) $companyId);
                    if (!$company->getIsPickup() && !$company->getIsDelivery()) {
                        $this->messageManager->addErrorMessage(
                            __('Please press submit order button to place the order.')
                        );
                        $defaultUrl = $this->urlModel->getUrl('checkout/cart/', ['_secure' => true]);
                        $resultRedirect = $this->resultRedirectFactory->create();

                        return $resultRedirect->setUrl($defaultUrl);
                    }
                }
            }
        }
        return $proceed();
    }
}
