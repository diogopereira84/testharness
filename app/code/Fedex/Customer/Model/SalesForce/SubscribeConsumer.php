<?php
declare(strict_types=1);

namespace Fedex\Customer\Model\SalesForce;

use Fedex\Customer\Api\Data\ConfigInterface;
use Fedex\Customer\Api\Data\SalesForceCustomerSubscriberInterface;
use Fedex\Customer\Api\SalesForceInterface;
use Psr\Log\LoggerInterface;

class SubscribeConsumer
{
    /**
     * @param ConfigInterface $configInterface
     * @param SalesForceInterface $salesForceApiInterface
     * @param LoggerInterface $logger
     */
    public function __construct(
        private ConfigInterface $configInterface,
        private SalesForceInterface $salesForceApiInterface,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @param SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriber
     * @return void
     */
    public function processMessage(SalesForceCustomerSubscriberInterface $salesForceCustomerSubscriber)
    {
        if (!$this->configInterface->isMarketingOptInEnabled()) {
            $this->logger->info('Marketing Opt-In Feature is Disabled!');
            return;
        }

        $this->salesForceApiInterface->subscribe($salesForceCustomerSubscriber);
    }
}
