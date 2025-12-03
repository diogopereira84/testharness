<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Olimjon Akhmedov <olimjon.akhmedov.osv@fedex.com>
 */

namespace Fedex\Mars\Helper;

use Fedex\Mars\Model\Client;
use Fedex\Mars\Model\ClientFactory;
use Fedex\Mars\Model\Config;
use Fedex\Mars\Model\JSONBuilder;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

/**
 * ProcessQueueMsg Helper
 */
class SendToMars extends AbstractHelper
{

    /**
     * @var Json
     */
    private Json $_json;

    /**
     * @param Context $context
     * @param ClientFactory $clientFactory
     * @param PublishToQueue $publish
     * @param JSONBuilder $JSONBuilder
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Config $moduleConfig
     */
    public function __construct(
        Context             $context,
        private ClientFactory       $clientFactory,
        private PublishToQueue      $publish,
        private JSONBuilder $JSONBuilder,
        Json                $json,
        private LoggerInterface     $logger,
        private Config     $moduleConfig
    ) {
        $this->_json = $json;
        parent::__construct($context);
    }

    /**
     * Send Order
     *
     * @param string $message
     */
    public function send(string $message)
    {
        $data = $this->_json->unserialize($message);
        if (isset($data[Config::MARS_QUEUE_ID]) && isset($data[Config::MARS_QUEUE_TYPE])) {
            $id = $data[Config::MARS_QUEUE_ID];
            $type = $data[Config::MARS_QUEUE_TYPE];
            if (!$this->moduleConfig->isEnabled()) {
                $this->publish->publish($id, $type);
                return;
            }
            $dataJson = $this->JSONBuilder->prepareJson($id, $type);
            if (!empty($dataJson)) {
                $client = $this->clientFactory->create();
                try {
                    $client->sendJson($dataJson, $id);
                } catch (\Exception $e) {
                    $this->logger->critical(
                        __METHOD__ . ':' .
                        __LINE__ . ':MARS id = ' . $id . ' type = ' .
                        $type . '.  Error sending data to Mars: ' . $e->getMessage()
                    );
                    $this->publish->publish($id, $type);
                    return;
                }
            }
        } else {
            $logMessage = __METHOD__ . ':' . __LINE__ . ":MARS - id or type is missing";
            $this->logger->critical($logMessage);
        }
    }
}
