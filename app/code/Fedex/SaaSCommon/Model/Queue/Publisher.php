<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Queue;

use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;

class Publisher
{
    const TOPIC_NAME = 'fedexSaaSCommonAllowedCustomerGroups';

    public function __construct(
        private PublisherInterface $publisher
    ) {
    }

    /**
     * Publish an entity request to the queue.
     *
     * @param AllowedCustomerGroupsRequestInterface $request
     */
    public function publish(AllowedCustomerGroupsRequestInterface $request): void
    {
        $this->publisher->publish(
            self::TOPIC_NAME,
            $request
        );
    }
}
