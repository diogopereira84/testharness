<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\Punchout\Helper\Data;
use Fedex\Header\Helper\Data as HeaderData;

/**
 * CenterDetails Controller
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class CenterDetails extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Context $context
     */
    protected $context;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param Curl $curl
     * @param JsonFactory $resultJsonFactory
     * @param Data $gateTokenHelper
     * @param HeaderData $headerData
     */
    public function __construct(
        Context $context,
        protected ScopeConfigInterface $configInterface,
        protected LoggerInterface $logger,
        protected RequestInterface $request,
        protected Curl $curl,
        protected JsonFactory $resultJsonFactory,
        protected Data $gateTokenHelper,
        protected HeaderData $headerData
    ) {
        parent::__construct($context);
    }

    /**
     * Calls the center details API to get center details response and returns the response in
     * JSON format
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $locationId = $this->request->getPostValue('locationId');
        $setupURL = $this->configInterface->getValue("fedex/general/location_details_api_url") . '/' .
            $locationId.'?startDate='.date("m-d-Y").'&views=30';
        $gateWayToken = $this->gateTokenHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = ["Content-Type: application/json", "Accept: application/json",
             "Accept-Language: json", $authHeaderVal . $gateWayToken];

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => ''
            ]
        );
        $this->curl->get($setupURL);
        $output = $this->curl->getBody();
        $arrayData = json_decode($output, true);
        if (isset($arrayData['errors']) || !isset($arrayData['output'])) {
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Center API Request');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $locationId . date('m-d-y'));
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Center API Response');
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ' . $output);
        }
        $arrayName = json_decode($output, true);
        if (!empty($arrayName)) {
            if (!array_key_exists('errors', $arrayName)) {
                $arraySortedCenters = $arrayName['output']['location']??$arrayName['output'];

                return $this->resultJsonFactory->create()->setData($arraySortedCenters);
            } else {

                return $this->resultJsonFactory->create()->setData($arrayName);
            }
        } else {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' No data being returned from center details api.');
            return "Error found no data";
        }
    }
}
