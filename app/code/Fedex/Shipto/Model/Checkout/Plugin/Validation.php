<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Model\Checkout\Plugin;

use Magento\CheckoutAgreements\Model\AgreementsProvider;
use Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use \Psr\Log\LoggerInterface;

/**
 * Class Validation validates the agreement based on the payment method
 */
class Validation extends \Magento\CheckoutAgreements\Model\Checkout\Plugin\Validation
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_coreSession;

    /**
     * @param \Magento\Checkout\Api\AgreementsValidatorInterface $agreementsValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration
     * @param \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList
     * @param ActiveStoreAgreementsFilter $activeStoreAgreementsFilter
     * @param CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Session\SessionManagerInterface $coreSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        private \Magento\Checkout\Api\AgreementsValidatorInterface $agreementsValidator,
        private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfiguration,
        private \Magento\CheckoutAgreements\Api\CheckoutAgreementsListInterface $checkoutAgreementsList,
        private \Magento\CheckoutAgreements\Model\Api\SearchCriteria\ActiveStoreAgreementsFilter $activeStoreAgreementsFilter,
        /**
         * Quote repository.
         */
        private CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Session\SessionManagerInterface $coreSession,
        protected \Magento\Framework\App\RequestInterface $request,
        protected LoggerInterface $logger
    ) {
        $this->_coreSession = $coreSession;
    }

    /**
     * Validates agreements before save payment information and  order placing.
     *
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($this->isAgreementEnabled()) {
            $this->validateAgreements($paymentMethod);
        }
    }

    /**
     * Check validation before saving the payment information
     *
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $subject
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function beforeSavePaymentInformation(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $quote = $this->quoteRepository->getActive($cartId);
        if ($this->isAgreementEnabled() && !$quote->getIsMultiShipping()) {
            $this->validateAgreements($paymentMethod);
        }
    }

    /**
     * Validate agreements base on the payment method
     *
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return void
     */
    protected function validateAgreements(\Magento\Quote\Api\Data\PaymentInterface $paymentMethod)
    {
        $agreements = $paymentMethod->getExtensionAttributes() === null ? []
            : $paymentMethod->getExtensionAttributes()->getAgreementIds();

        if (!$this->agreementsValidator->isValid($agreements)) {
            $this->logger->info(__METHOD__.':'.__LINE__.':The order wasn\'t placed. Conditions not agreed to.');
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __(
                    "The order wasn't placed."
                    . "First, agree to the terms and conditions, then try placing your order again."
                )
            );
        }
    }

    /**
     * Verify if agreement validation needed.
     *
     * @return bool
     */
    private function isAgreementEnabled()
    {
        $requestData = json_decode($this->request->getContent(), true);

        if (!empty($requestData)
            && isset($requestData['paymentMethod']['po_number'])
                && $requestData['paymentMethod']['po_number'] != "") {
                return false;
        }
        
        $isAgreementsEnabled = $this->scopeConfiguration->isSetFlag(
            AgreementsProvider::PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        $agreementsList = $isAgreementsEnabled
            ? $this->checkoutAgreementsList->getList($this->activeStoreAgreementsFilter->buildSearchCriteria())
            : [];
        return (bool)($isAgreementsEnabled && count($agreementsList) > 0);
    }
}
