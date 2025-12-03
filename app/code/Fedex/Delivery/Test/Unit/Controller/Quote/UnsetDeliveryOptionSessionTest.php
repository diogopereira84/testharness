<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Controller\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Controller\Quote\UnsetDeliveryOptionSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class UnsetDeliveryOptionSessionTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $unsetDeliveryOptionSession;
    /**
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var JsonFactory $jsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods([
                'unsCustomShippingMethodCode',
                'unsCustomShippingCarrierCode',
                'unsCustomShippingTitle',
                'unsCustomShippingPrice',
                'unsDeliveryOptions',
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->unsetDeliveryOptionSession = $this->objectManagerHelper->getObject(
            UnsetDeliveryOptionSession::class,
            [
                'checkoutSession' => $this->checkoutSession,
                'jsonFactory' => $this->jsonFactory,
                'logger' => $this->logger
            ]
        );
    }

    /**
     * Test execute method
     *
     */
    public function testExecute()
    {
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingMethodCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingCarrierCode')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingTitle')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsCustomShippingPrice')->willReturnSelf();
        $this->checkoutSession->expects($this->any())->method('unsDeliveryOptions')->willReturnSelf();
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactory, $this->unsetDeliveryOptionSession->execute());
    }

    /**
     * Test execute method with exception
     *
     */
    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->checkoutSession->expects($this->any())->method('unsCustomShippingMethodCode')->willThrowException($exception);
        $this->jsonFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->jsonFactory->expects($this->any())->method('setData')->willReturnSelf();

        $this->assertEquals($this->jsonFactory, $this->unsetDeliveryOptionSession->execute());
    }
}
