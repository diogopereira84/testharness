<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\CreditCard;

use Magento\Framework\App\ActionInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\EnhancedProfile\ViewModel\CompanyPaymentData;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class RemoveSharedCreditCardInfo implements ActionInterface
{
    public const ERROR = 'error';
    public const SUCCESS = 'success';
    public const SYSTEM_ERROR = 'System error, Please try again.';
    public const TOGGLE_KEY ='explorers_company_settings_customer_admin';

    /**
     * Initialize dependencies.
     *
     * @param LoggerInterface $logger
     * @param JsonFactory $jsonFactory
     * @param AdditionalDataFactory $additionalDataFactory
     * @param CompanyPaymentData $companyPaymentData
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected JsonFactory $jsonFactory,
        protected AdditionalDataFactory $additionalDataFactory,
        protected CompanyPaymentData $companyPaymentData,
        protected RequestInterface $request,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Remove shared credit cart information
     *
     * @return json
     */
    public function execute()
    {
        $response = [];
        $companyId = $this->request->getParam('companyId');
        try {
            $companyObject = $this->companyPaymentData->getCompanyDataById();
            if (!$companyObject->isEmpty()) {
                $companyObject->setCcToken(null);
                $companyObject->setCcData(null);
                $companyObject->setCcTokenExpiryDateTime(null);
                $companyObject->save();
                $this->uncheckNonEditablePaymentMethod($companyId);
                $response['status'] = self::SUCCESS;
                if ($this->toggleConfig->getToggleConfigValue(self::TOGGLE_KEY)) {
                    $response['message'] = __("Site level payments credit card has been removed successfully.");
                } else {
                    $response['message'] = __("Shared credit card has been removed successfully.");
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error while removing the shared credit card info: '. $e->getMessage()
            );
            $response['status'] = self::ERROR;
            $response['message'] = self::SYSTEM_ERROR;
        }

        $result = $this->jsonFactory->create();
        $result->setData($response);

        return $result;
    }

    /**
     * Uncheck non-editable payment method flag
     *
     * @param int $companyId
     * @return boolean
     */
    public function uncheckNonEditablePaymentMethod($companyId)
    {
        try {
            if ($companyId) {
                $compAddDataCollection = $this->additionalDataFactory->create()
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $companyId])
                    ->getFirstItem();

                if ($compAddDataCollection) {
                    $compAddDataCollection->setData('is_non_editable_cc_payment_method', 0);
                    $compAddDataCollection->save();
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__. ' Non-editable payment method unchecked successfully'
                    );
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error while uncheck non-editable payment method: ' . $e->getMessage()
            );

            return false;
        }
    }
}
