<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Fedex\Shipto\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Psr\Log\LoggerInterface;

class GetLocation implements ActionInterface
{
    public const ERROR = 'error';
    public const SYSTEM_ERROR = 'System error, Please try again.';
    public const PLEASE_PROVIDE_POSTAL_CODE = 'Please provide postal code.';

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param Data $data
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     * @param Session $customerSession
     * @param ToggleConfig $toggleConfig
     * @param RequestInterface $request
     */
    public function __construct(
        private Context $context,
        protected Data $data,
        protected JsonFactory $jsonFactory,
        protected LoggerInterface $logger,
        protected Session $customerSession,
        private ToggleConfig $toggleConfig,
        private RequestInterface $request
    )
    {
    }

    /**
     * Get location based on zipcode
     *
     * @return json
     */
    public function execute()
    {
        $logHeader = 'File: ' . static::class . ' Method: ' . __METHOD__;
        $customerEmail = null;
        if ($this->customerSession->getCustomer() !== null) {
            $customerEmail = $this->customerSession->getCustomer()->getEmail();
        }
        $data = $this->request->getParams();
        $response = [];
        if (isset($data['zipcode']) && $data['zipcode'] != "") {
            // D-217639 Unable to update the preferred pickup location in FCL profile (Retail/Commercial)
            if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_217639')) {
                $dataAllocationResponse = $this->data->getAllLocationsByZip($data, false);
                if ($dataAllocationResponse && isset($dataAllocationResponse[0]['locationId'])) {
                    $response['locations'] = $dataAllocationResponse;
                } else {
                    $response['status'] = self::ERROR;
                    $response['noLocation'] = 1;
                    $response['message'] = self::SYSTEM_ERROR;
                }
            } else {
                $dataAllocationResponse = $this->data->getAllLocationsByZip($data);
                if ($dataAllocationResponse && isset($dataAllocationResponse['success'])
                    && isset($dataAllocationResponse['locations'])
                    && $dataAllocationResponse['success'] == 1
                    && $dataAllocationResponse['locations'] != "") {
                    $response['locations'] = $dataAllocationResponse['locations'];
                } else {
                    $response['status'] = self::ERROR;
                    $response['noLocation'] = 1;
                    $response['message'] = self::SYSTEM_ERROR;
                }
            }
        } else {
                $response['status'] = self::ERROR;
                $response['message'] = self::PLEASE_PROVIDE_POSTAL_CODE;
        }

        $this->logger->info($logHeader . ' Line:' . __LINE__ . 'Get location for customer:'
        . $customerEmail . var_export(json_encode($response), true));

        $result = $this->jsonFactory->create();
        $result->setData($response);
        return $result;
    }
}
