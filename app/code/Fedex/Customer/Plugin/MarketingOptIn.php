<?php
/**
 * @category Fedex
 * @package  Fedex_Customer
 * @copyright  Copyright (c) 2023 Fedex
 * @author  Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Customer\Plugin;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Model\SalesForce\SubscribePublisher;
use Fedex\SubmitOrderSidebar\Controller\Quote\SubmitOrderOptimized;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;

class MarketingOptIn
{
    /**
     * @param RequestInterface $request
     * @param ConfigInterface $config
     * @param SubscribePublisher $salesForceSubscribePublisher
     * @param JsonValidator $jsonValidator
     * @param Json $json
     */
    public function __construct(
        protected RequestInterface $request,
        protected ConfigInterface $config,
        protected SubscribePublisher $salesForceSubscribePublisher,
        protected JsonValidator $jsonValidator,
        protected Json $json
    )
    {
    }

    /**
     * @param SubmitOrderOptimized $subject
     * @param $result
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function afterExecute(SubmitOrderOptimized $subject, $result) //NOSONAR
    {
        if ($this->config->isMarketingOptInEnabled()) {
            $requestData = $this->request->getPost('data');
            if ($this->jsonValidator->isValid($requestData)) {
                $requestData = $this->json->unserialize($requestData);
                if (is_array($requestData) && isset($requestData['marketingOptIn'])
                    && $marketingOptIn = $requestData['marketingOptIn']) {
                    if ($this->jsonValidator->isValid($marketingOptIn)) {
                        $marketingOptIn = $this->json->unserialize($marketingOptIn);
                        $this->salesForceSubscribePublisher->execute($marketingOptIn);
                    }
                }
            }
        }

        return $result;
    }
}
