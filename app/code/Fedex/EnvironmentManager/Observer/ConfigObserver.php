<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnvironmentManager\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\RequestInterface;
use \Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ConfigObserver implements ObserverInterface
{
    /**
     * Toggle configuration constructor
     *
     * @param Logger $logger
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Logger $logger,
        private RequestInterface $request,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Save toggle configuration in cache
     *
     * @return void
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $configurationData = $this->request->getParam('groups');
            $cacheData = [];
            if (isset($configurationData["environment_toggle"]["fields"])
                && !empty($configurationData["environment_toggle"]["fields"])) {
                foreach ($configurationData["environment_toggle"]["fields"] as $key => $toggleConfigValue) {
                    $cacheData[$key] = $toggleConfigValue["value"];
                }
            }
            $this->toggleConfig->saveToggleConfigCache($cacheData);
            
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
            . ' Error in saving toggle configuration: ' . $e->getMessage());
        }
    }
}
