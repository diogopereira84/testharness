<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceWebhook\Model;

use Fedex\MarketplaceWebhook\Api\MiraklWebhookInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Fedex\MarketplaceWebhook\Model\Middleware\AuthorizationMiddleware;
use Psr\Log\LoggerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Fedex\MarketplaceCheckout\Model\Config\HandleMktCheckout;
use Fedex\MarketplaceWebhook\Model\Middleware\MiraklWebhookDuplicateBlocker;

class MiraklWebhook implements MiraklWebhookInterface
{

    /**
     * @param AuthorizationMiddleware $authorizationMiddleware
     * @param HttpRequest $request
     * @param LoggerInterface $logger
     * @param PublisherInterface $publisher
     * @param HandleMktCheckout $handleMktCheckout
     */
    public function __construct(
        private AuthorizationMiddleware     $authorizationMiddleware,
        private HttpRequest                 $request,
        private LoggerInterface             $logger,
        private PublisherInterface          $publisher,
        private HandleMktCheckout           $handleMktCheckout,
        private MiraklWebhookDuplicateBlocker $duplicateBlocker
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function handleWebhook()
    {
        $this->authorizationMiddleware->validateAuthorizationHeader();

        $payload = $this->request->getContent();

        if ($this->handleMktCheckout->getDuplicateWebhookBlockerToggle()) {
            if ($this->duplicateBlocker->isDuplicate($payload)) {
                $this->logger->info(__METHOD__ . ': Duplicate webhook ignored.');
                $this->authorizationMiddleware->sendSuccessResponse('Duplicate webhook ignored');
                return;
            }
        }

        try {
            $this->publisher->publish('miraklWebhookProcessor', $payload);
            $this->authorizationMiddleware->sendSuccessResponse('Webhook data added to the queue successfully');
            if ($this->handleMktCheckout->getTogglePayloadWebhookLogs()) {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' WEBHOOK PAYLOAD ' . $this->request->getContent());
            }
        } catch (\Exception $e) {
            // Even if there is an error, we should pass 200 to webhook so it doesn't hang
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $this->request->getContent());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->authorizationMiddleware->sendErrorResponse($e->getMessage());
        }
    }
}
