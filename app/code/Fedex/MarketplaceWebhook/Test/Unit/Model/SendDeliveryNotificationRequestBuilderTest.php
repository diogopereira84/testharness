<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model;

use Fedex\MarketplaceWebhook\Model\SendDeliveryNotificationRequestBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\ConfigurationMismatchException;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Helper\Context;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use PHPUnit\Framework\TestCase;
use Fedex\Header\Helper\Data as HeaderData;

class SendDeliveryNotificationRequestBuilderTest extends TestCase
{
    /**
     * @var SendDeliveryNotificationRequestBuilder
     */
    private SendDeliveryNotificationRequestBuilder $sendDeliveryNotificationRequestBuilder;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var ScopeConfigInterface
     */
    private $configInterface;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var PunchoutHelper
     */
    private $punchoutHelper;

    /**
     * @var HeaderData
     */
    private $headerData;


    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->configInterface = $this->createMock(ScopeConfigInterface::class);
        $this->customerSession = $this->createMock(Session::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->curl = $this->createMock(Curl::class);
        $this->punchoutHelper = $this->createMock(PunchoutHelper::class);
        $this->headerData = $this->createMock(HeaderData::class);

        $this->sendDeliveryNotificationRequestBuilder = new SendDeliveryNotificationRequestBuilder(
            $this->context,
            $this->configInterface,
            $this->customerSession,
            $this->logger,
            $this->curl,
            $this->punchoutHelper,
            $this->headerData
        );
    }

    /**
     * Test sendDeliverNotification method.
     *
     * @return void
     */
    public function testSendDeliverNotification(): void
    {
        $decodedData = [
            'retailTransactionId' => '123',
            'orderNumber' => '456',
            'vendorCartId' => '789',
            'deliveryRefId' => '789',
        ];
        $this->configInterface->expects($this->once())->method('getValue')
            ->with("fedex/general/delivery_notification_url", ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->curl->expects($this->atMost(2))->method('getBody')
            ->willReturn(json_encode($decodedData));
        $this->sendDeliveryNotificationRequestBuilder->sendDeliverNotification($decodedData);
    }

    /**
     * Test sendDeliverNotification method.
     *
     * @return void
     */
    public function testSendDeliverNotificationError(): void
    {
        $decodedData = [
            'retailTransactionId' => '123',
            'orderNumber' => '456',
            'vendorCartId' => '789',
            'deliveryRefId' => '789',
            'error' => 'forced error'
        ];
        $this->configInterface->expects($this->once())->method('getValue')
            ->with("fedex/general/delivery_notification_url", ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $this->curl->expects($this->atMost(2))->method('getBody')
            ->willReturn(json_encode($decodedData));
        $this->sendDeliveryNotificationRequestBuilder->sendDeliverNotification($decodedData);
    }

    /**
     * Test sendDeliverNotification method.
     *
     * @return void
     */
    public function testSendDeliverNotificationThrowException(): void
    {
        $decodedData = [
            'retailTransactionId' => '123',
            'orderNumber' => '456',
            'vendorCartId' => '789',
            'deliveryRefId' => '789',
        ];
        $this->sendDeliveryNotificationRequestBuilder->sendDeliverNotification($decodedData);
    }

    /**
     * Test getUrl method.
     *
     * @return void
     */
    public function testGetUrl(): void
    {
        $apiURL = 'https://example.com/api';

        $this->configInterface->expects($this->once())
            ->method('getValue')
            ->with('fedex/general/delivery_notification_url', ScopeInterface::SCOPE_STORE)
            ->willReturn($apiURL);

        $this->assertSame($apiURL, $this->sendDeliveryNotificationRequestBuilder->getUrl());
    }
}
