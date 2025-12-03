<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Index;

use Fedex\SSO\Model\ToggleConfig;
use Magento\Framework\App\ActionInterface;
use Magento\Customer\Model\Session;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use \Psr\Log\LoggerInterface;

/**
 * CustomerBillingAddress Controller class
 */
class CustomerBillingAddress implements ActionInterface
{
    /**
     * Customer billing address constructor
     *
     * @param Session $customerSession
     * @param SsoConfiguration $ssoConfiguration
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private Session $customerSession,
        private SsoConfiguration $ssoConfiguration,
        private JsonFactory $jsonFactory,
        private LoggerInterface $logger,
        private ToggleConfig $toggleConfig
    ) {
    }

    /**
     * Get customer billing address
     *
     * @return Json
     */
    public function execute()
    {
        /**
         * When removing this toggle remove the conditional and the remaining code after the conditional
         */
        if ($this->toggleConfig->isToggleD201662Enabled()) {
            $result = $this->jsonFactory->create();
        }
        if ($this->ssoConfiguration->isFclCustomer()) {
            $billingAddress = $this->ssoConfiguration
                                ->getDefaultBillingAddressById($this->customerSession->getCustomerId());
            if (is_array($billingAddress)) {
                $company = isset($billingAddress['company']) ? $billingAddress['company'] : '';
                $street = isset($billingAddress['street'][0]) ? $billingAddress['street'][0] : '';
                $city = isset($billingAddress['city']) ? $billingAddress['city'] : '';
                $region = isset($billingAddress['region']['region_code']) ?
                            $billingAddress['region']['region_code'] : '';
                $postcode = isset($billingAddress['postcode']) ? $billingAddress['postcode'] : '';
                $data = [
                            'status' => 'success',
                            'company' => $company,
                            'street' => $street,
                            'city' => $city,
                            'region' => $region,
                            'postcode' => $postcode
                        ];
            } else {
                $data = ['status' => 'Failure', 'message' => $billingAddress];
                $this->logger->error(__METHOD__.':'.__LINE__.': failed to get customer address for customer id: '. $this->customerSession->getCustomerId());
            }
            $result = $this->jsonFactory->create();
            $result->setData($data);
            $this->logger->info(__METHOD__.':'.__LINE__.': Get customer address success ');
            return $result;
        }
        /**
         * When removing this toggle remove the conditional and the remaining code after the conditional
         */
        if ($this->toggleConfig->isToggleD201662Enabled()) {
            return $result;
        }
    }
}
