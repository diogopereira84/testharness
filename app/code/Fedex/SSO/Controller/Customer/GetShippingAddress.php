<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Customer;

use Magento\Framework\App\ActionInterface;
use Magento\Customer\Model\Session;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Framework\Controller\Result\JsonFactory;
use \Psr\Log\LoggerInterface;

/**
 * GetShippingAddress Controller class
 */
class GetShippingAddress implements ActionInterface
{
    /**
     * GetShippingAddress constructor
     *
     * @param Session $customerSession
     * @param SsoConfiguration $ssoConfiguration
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected Session $customerSession,
        protected SsoConfiguration $ssoConfiguration,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Customer logout from application
     *
     * @return \Magento\Framework\Controller\Result\Json $response
     */
    public function execute()
    {
        if ($this->ssoConfiguration->isFclCustomer()) {
            $shippingAddress = $this->ssoConfiguration
                                ->getDefaultShippingAddressById($this->customerSession->getCustomerId());

            if (is_array($shippingAddress)) {
                $firstname = isset($shippingAddress['firstname']) ? $shippingAddress['firstname'] : '';
                $lastname = isset($shippingAddress['lastname']) ? $shippingAddress['lastname'] : '';
                $email = isset($shippingAddress['custom_attributes']['email_id']['value']) ?
                $shippingAddress['custom_attributes']['email_id']['value'] : '';

                $company = isset($shippingAddress['company']) ? $shippingAddress['company'] : '';
                $street = isset($shippingAddress['street'][0]) ? $shippingAddress['street'][0] : '';
                $city = isset($shippingAddress['city']) ? $shippingAddress['city'] : '';
                $region = isset($shippingAddress['region']['region_id']) ?
                            $shippingAddress['region']['region_id'] : '';
                $postcode = isset($shippingAddress['postcode']) ? $shippingAddress['postcode'] : '';
                $telephone = isset($shippingAddress['telephone']) ?
                            '('.substr($shippingAddress['telephone'], 0, 3).') '
                            .substr($shippingAddress['telephone'], 3, 3).'-'
                            .substr($shippingAddress['telephone'], 6, 4): '';

                $ext = isset($shippingAddress['custom_attributes']['ext']['value']) ?
                $shippingAddress['custom_attributes']['ext']['value'] : '';
                $streetOne = isset($shippingAddress['street'][0]) ? $shippingAddress['street'][0] : '';
                $streetTwo = isset($shippingAddress['street'][1]) ? $shippingAddress['street'][1] : '';
                $data = [
                            'status' => 'success',
                            'firstname' => $firstname,
                            'lastname' => $lastname,
                            'email' => $email,
                            'company' => $company,
                            'street' => $street,
                            'city' => $city,
                            'region' => $region,
                            'postcode' => $postcode,
                            'telephone' => $telephone,
                            'ext' => $ext,
                            'streetOne' => $streetOne,
                            'streetTwo' => $streetTwo
                        ];
            } else {
                $data = ['status' => 'Failure', 'message' => $shippingAddress];
                $this->logger->error(__METHOD__.':'.__LINE__.': failed to get shipping address for customerId: '. $this->customerSession->getCustomerId());
            }
            $result = $this->jsonFactory->create();
            $result->setData($data);
            $this->logger->info(__METHOD__.':'.__LINE__.': Get shipping address success.');
            return $result;
        }
    }
}
