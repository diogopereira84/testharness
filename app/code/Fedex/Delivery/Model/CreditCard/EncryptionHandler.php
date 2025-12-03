<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Model\CreditCard;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Header\Helper\Data as HeaderData;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Laminas\Diactoros\HeaderSecurity;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

/**
 * B-1205796 : API integration for CC details and Billing details in Magento Admin
 */
class EncryptionHandler extends AbstractModel
{
    /**
     * Encryption API URL config path
     */
    const XML_PATH_ENCRYPTION_API_URL = 'fedex/general/encryption_api_url';

    /**
     * EncryptionHandler Constructor
     *
     * @param ScopeConfigInterface $configInterface
     * @param DeliveryHelper $deliveryHelper
     * @param PunchoutHelper $punchoutHelper
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param HeaderData $headerData
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     *
     */
    public function __construct(
        Context $context,
        Registry $registry,
        protected ScopeConfigInterface $configInterface,
        protected DeliveryHelper $deliveryHelper,
        protected PunchoutHelper $punchoutHelper,
        protected LoggerInterface $logger,
        protected Curl $curl,
        protected HeaderData $headerData,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Calls the center details API to get center details response
     *
     * @return array
     */
    public function getEncryptionKey()
    {
        $setupURL = $this->configInterface->getValue(self::XML_PATH_ENCRYPTION_API_URL);
        $gatewayToken = $this->punchoutHelper->getAuthGatewayToken();
        $authHeaderVal = $this->headerData->getAuthHeaderValue();
        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "Accept-Language: json",
            $authHeaderVal . $gatewayToken,
        ];

        $this->curl->setOptions(
            [
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_ENCODING => '',
            ]
        );
        $this->curl->get($setupURL);
        $output = $this->curl->getBody();
        $outputData = json_decode($output, true);
        if (isset($outputData['errors']) || !isset($outputData['output'])) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
             ' Encryption key API Error: ' . $outputData['errors']);

            return $outputData;
        } else {
            return $outputData['output'];
        }
    }

    /**
     * Get gateway token for API call
     *
     * @return string
     */
    private function getGatewayToken()
    {
        return $this->punchoutHelper->getGatewayToken();
    }
}
