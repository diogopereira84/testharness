<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use \Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use \Magento\Framework\HTTP\Client\Curl;
use \Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\Header\Helper\Data as HeaderData;

class SendDeliveryNotificationRequestBuilder
{
    private const DELIVERED = 'Delivered';

    /**
     * @param Context $context
     * @param ScopeConfigInterface $configInterface
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param Curl $curl
     * @param HeaderData $headerData
     */
    public function __construct(
        private Context $context,
        private ScopeConfigInterface $configInterface,
        private Session              $customerSession,
        private LoggerInterface      $logger,
        private Curl                 $curl,
        private PunchoutHelper       $punchoutHelper,
        private HeaderData $headerData
    ) {
    }

    /**
     * Send delivery notification.
     *
     * @return String
     */
    public function sendDeliverNotification($decodedData)
    {
        $apiURL = $this->getUrl();

        try {
            $params = array(
                'statusNotification' => array(
                    'retailTransactionId' => $decodedData['retailTransactionId'],
                    'orderNumber'         => $decodedData['orderNumber'],
                    'deliveryRefId'       => $decodedData['deliveryRefId'],
                    'status'              => self::DELIVERED,
                )
            );

            if (!$apiURL) {
                throw new ConfigurationMismatchException(__('Missing Gateway Token Configuration!'));
            }
            $authHeaderVal = $this->headerData->getAuthHeaderValue();
            $dataString = json_encode($params);

            $headers = [
                "Content-Type: application/json",
                "Accept: application/json",
                "Accept-Language: json",
                "Content-Length: " . strlen($dataString),
                $authHeaderVal . $this->punchoutHelper->getAuthGatewayToken(),
                "Cookie: Bearer=" . $this->punchoutHelper->getTazToken()
            ];

            $this->curl->setOptions(
                [
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $dataString,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '',
                ]
            );

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ . 'Send delivery notification API request payload: ' . $dataString
            );

            $this->curl->post($apiURL, $dataString);

            $response = json_decode($this->curl->getBody(), true);

            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .'Send delivery notification API response: ' . $this->curl->getBody()
                . 'Status code: ' .  $this->curl->getStatus()
            );

            if (isset($response['error'])) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $response['error']);
                throw new LocalizedException(__($response['error']));
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Gateway Fedex Rates Token API Error: ' . $e->getMessage()
            );
            return null;
        }
    }

    /**
     * Get URL.
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->configInterface->getValue(
            "fedex/general/delivery_notification_url",
            ScopeInterface::SCOPE_STORE
        );
    }
}
