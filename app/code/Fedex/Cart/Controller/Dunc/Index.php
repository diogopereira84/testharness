<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Controller\Dunc;


use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    private const ERRORS = 'errors';

    protected $cartHelper;

    /**
     * Execute Subtotal Controller
     *
     * @param Context $context
     * @param CartFactory $cartFactory
     * @param FXORate $fxoRateHelper
     * @param FXORateQuote $fxoRateQuote
     * @param JsonFactory $resultJsonFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        protected ToggleConfig $toggleConfig,
        protected Curl $curl,
        protected Session $customerSession,
        protected ScopeConfigInterface $configInterface,
        protected LoggerInterface $logger,
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Method.
     */
    public function execute()
    {
        try {
            $arrRequestResponse = $this->customerSession->getDuncResponse() ?? [];
            $callDuncApiResponse = $arrRequestResponse;
            $resultJson = $this->resultJsonFactory->create();
            $requestData = $this->getRequest()->getPostValue('imageId');
            if($requestData) {
                $callDuncApiResponse = $this->callDuncApi($requestData);
                if (isset($callDuncApiResponse['output']['imageByteStream'])) {
                    $arrRequestResponse[$requestData] =  $callDuncApiResponse['output']['imageByteStream'];
                    $this->customerSession->setDuncResponse($arrRequestResponse);
                }
            }
        } catch (\Exception $error) {

            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Error found no data from DuncApi: ' . $error->getMessage());
            $callDuncApiResponse = [self::ERRORS => "Error found no data from DuncApi"  . $error->getMessage()];
        }

        return $resultJson->setData($callDuncApiResponse);
    }

    /**
     * Call callDuncApi API
     */
    public function callDuncApi($contentReferenceId)
    {
        $setupURL = $this->configInterface->getValue("fedex/general/dunc_office_api_url");
        $setupURL = str_replace('contentReference', $contentReferenceId, $setupURL);
        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
            ]
        );
        $this->curl->get($setupURL);
        $output = $this->curl->getBody();
        return  json_decode($output, true);
    }
}
