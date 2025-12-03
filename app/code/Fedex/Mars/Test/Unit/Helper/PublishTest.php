<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2023 FedEx
 * @author      ManoKarthick <mano.karthick.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Mars\Test\Unit\Helper;

use ArgumentCountError;
use Fedex\Mars\Helper\PublishToQueue;
use Fedex\Mars\Model\ClientFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class PublishTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Fedex\Mars\Model\ClientFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $clientFactoryMock;
    protected $publisherMock;
    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonMock;
    protected $PublishMock;
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->clientFactoryMock = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $className = PublishToQueue::class;
        $this->PublishMock = $objectManagerHelper->getObject(
            $className,
            [
                'context' => $this->contextMock,
                'clientFactory' => $this->clientFactoryMock,
                'publisher' => $this->publisherMock,
                'json' => $this->jsonMock
            ]
        );
    }

    public function testPublishArgumentCountError(): void
    {
        $this->expectException(ArgumentCountError::class);
        $this->PublishMock->publish();
    }

    public function testPublishMessageMalformed(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->publisherMock->method('publish')->willThrowException(new \InvalidArgumentException);
        $this->PublishMock->publish(1, 'order');
    }
}
