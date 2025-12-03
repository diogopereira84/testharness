<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Header\Helper\Data as HeaderDataHelper;
use Magento\Framework\HTTP\Client\Curl;
use Fedex\Cart\Helper\Data as CartDataHelper;

/**
 * RateQuoteHelper module helper class
 */
class RateQuoteHelper extends AbstractHelper
{
    /**
     * FuseBidHelper Constructor
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param PunchoutHelper $punchoutHelper
     * @param HeaderDataHelper $headerDataHelper
     * @param Curl $curl
     * @param CartDataHelper $cartDataHelper;
     */
    public function __construct(
        Context $context,
        protected LoggerInterface $logger,
        protected PunchoutHelper $punchoutHelper,
        protected HeaderDataHelper $headerDataHelper,
        protected Curl $curl,
        protected CartDataHelper $cartDataHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Get RateQuote details
     *
     * @param string $fjmpQuoteId
     * @return array 
     */
    public function getRateQuoteDetails($fjmpQuoteId) {
        $isApiCallSucceed = true;
        $message = '';
        try {
            $authenticationDetails = $this->getAuthenticationDetails();
            $authHeaderVal = $this->headerDataHelper->getAuthHeaderValue();
            $setupURL = $this->cartDataHelper->getRateQuoteApiUrl().'/'.$fjmpQuoteId;
            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                $authHeaderVal . $authenticationDetails['gateWayToken'],
                "Cookie: Bearer=" . $authenticationDetails['accessToken']
            ];

            $rateQuoteDetails = $this->callRateQuoteDetailsApi($setupURL, $headers);
            if ($rateQuoteDetails['errors']) {
                $isApiCallSucceed = false;
                $message = 'Error in ratequote details API. '.var_export($rateQuoteDetails['errors'], true);
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Error in ratequote details API '. var_export($rateQuoteDetails['errors'], true));
            } 
        } catch (\Exception $e) {
            $isApiCallSucceed = false;
            $message = 'Some exception happened while calling ratequote details API.';
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Some exception happened while calling ratequote details API ' . $e->getMessage());
        }

        return [
            'isApiCallSucceed' => $isApiCallSucceed,
            'message' => $message
        ];
    }

    /**
     * Call RateQuote Details API
     *
     * @return array
     */
    public function callRateQuoteDetailsApi($setupURL, $headers)
    {
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

        return json_decode($output, true);
    }

    /**
     * Get authentication details
     *
     * @return array
     */
    public function getAuthenticationDetails()
    {
        $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
        $accessToken = $this->punchoutHelper->getTazToken();

        return [
            'gateWayToken' => $gateWayToken,
            'accessToken' => $accessToken
        ];
    }
}
