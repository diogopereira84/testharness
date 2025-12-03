<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Test\Unit\Model;

use Fedex\MarketplaceWebhook\Model\MiraklWebhook;
use Fedex\MarketplaceWebhook\Model\Middleware\AuthorizationMiddleware;
use Magento\Framework\App\Request\Http as HttpRequest;
use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceWebhook\Model\Middleware\MiraklWebhookDuplicateBlocker;

class MiraklWebhookTest extends TestCase
{
    /**
     * @var MiraklWebhook
     */
    private MiraklWebhook $miraklWebhook;

    /**
     * @var AuthorizationMiddleware
     */
    private AuthorizationMiddleware $authorizationMiddleware;

    /**
     * @var HttpRequest
     */
    private HttpRequest $request;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    private HandleMktCheckout $handleMktCheckout;

    private MiraklWebhookDuplicateBlocker $miraklWebhookDuplicateBlocker;
    protected function setUp(): void
    {
        $this->authorizationMiddleware = $this->createMock(AuthorizationMiddleware::class);
        $this->request = $this->createMock(HttpRequest::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->handleMktCheckout = $this->createMock(HandleMktCheckout::class);
        $this->miraklWebhookDuplicateBlocker = $this->createMock(MiraklWebhookDuplicateBlocker::class);

        $this->miraklWebhook = new MiraklWebhook(
            $this->authorizationMiddleware,
            $this->request,
            $this->logger,
            $this->publisher,
            $this->handleMktCheckout,
            $this->miraklWebhookDuplicateBlocker
        );
    }

    /**
     * Test handleWebhook method.
     */
    public function testHandleWebhook(): void
    {
        $this->authorizationMiddleware->expects($this->once())
            ->method('validateAuthorizationHeader');

        $this->request->expects($this->any())
            ->method('getContent')
            ->willReturn('Webhook data');

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with('miraklWebhookProcessor', 'Webhook data');

        $this->authorizationMiddleware->expects($this->once())
            ->method('sendSuccessResponse')
            ->with('Webhook data added to the queue successfully');

        $this->miraklWebhook->handleWebhook();
    }

    /**
     * Test handleWebhook exception.
     */
    public function testHandleWebhookWithException(): void
    {
        $this->authorizationMiddleware->expects($this->once())
            ->method('validateAuthorizationHeader');

        $this->request->expects($this->any())
            ->method('getContent')
            ->willReturn('Webhook data');

        $exceptionMessage = 'Test exception';
        $exception = new \Exception($exceptionMessage);

        $this->publisher->expects($this->once())
            ->method('publish')
            ->with('miraklWebhookProcessor', 'Webhook data')
            ->willThrowException($exception);

        $this->authorizationMiddleware->expects($this->once())
            ->method('sendErrorResponse')
            ->with($exceptionMessage);

        $this->miraklWebhook->handleWebhook();
    }
}
