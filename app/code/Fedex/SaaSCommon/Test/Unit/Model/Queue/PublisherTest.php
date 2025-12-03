<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Queue;

use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Fedex\SaaSCommon\Model\Queue\Publisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    public function testPublishSendsCorrectDataToQueue()
    {
        $mockPublisher = $this->createMock(PublisherInterface::class);
        $entityId = 123;
        $entityType = 'product';

        $request = $this->createMock(AllowedCustomerGroupsRequestInterface::class);
        $request->method('getEntityId')->willReturn(null);
        $request->method('getEntityType')->willReturn(null);

        $mockPublisher->expects($this->once())
            ->method('publish')
            ->with(Publisher::TOPIC_NAME, $request);

        $publisher = new Publisher($mockPublisher);
        $publisher->publish($request);
    }
}

