<?php
declare(strict_types=1);

namespace Fedex\CustomerCanvas\Observer;

use Fedex\CustomerCanvas\Model\Service\CustomerCanvasUserMergeService;
use Fedex\CustomerCanvas\Model\Service\DocumentVendorOwnerUpdater;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\CustomerCanvas\Model\Service\CanvasQuoteService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CustomerCanvas\Model\ConfigProvider;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Cart;

class MergeQuote implements ObserverInterface
{
    public function __construct(
        protected ConfigProvider $configProvider,
        protected CustomerCanvasUserMergeService $canvasUserMergeService,
        private readonly CanvasQuoteService $canvasQuoteService,
        private readonly LoggerInterface $logger,
        private readonly DocumentVendorOwnerUpdater $documentVendorOwnerUpdater,
        private readonly Cart $cart
    ) {}

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        try {
            $quote = $observer->getEvent()->getSource();
            $customer = $observer->getEvent()->getQuote()->getCustomer();

            if (!$this->isEligibleForMerge($customer)) {
                return $this;
            }

            if (!$this->canvasQuoteService->quoteHasCanvasProduct($quote)) {
                return $this;
            }

            $guestUserId = $this->canvasQuoteService->getVendorOptionFromCanvasItem($quote);
            if (empty($guestUserId)) {
                return $this;
            }

            $response = $this->canvasUserMergeService->merge($customer->getId(), $guestUserId);
            if (!$response) {
                return $this;
            }

            $this->updateVendorOptions($quote, $response);

        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->clearCart();
            $this->logger->error(
                sprintf(
                    '%s:%s MergeQuote Observer Error: %s',
                    __METHOD__,
                    __LINE__,
                    $e->getMessage()
                ),
                ['PHPSESSID' => $this->configProvider->getSessionId()]
            );
        } catch (\Throwable $e) {
            $this->clearCart();
            $this->logger->critical(
                sprintf(
                    '%s:%s Unexpected error in MergeQuote observer: %s',
                    __METHOD__,
                    __LINE__,
                    $e->getMessage()
                ),
                ['PHPSESSID' => $this->configProvider->getSessionId()]
            );
        }

        return $this;
    }

    /**
     * Check if the customer and feature are eligible.
     */
    private function isEligibleForMerge($customer): bool
    {
        return $customer && $customer->getId() && $this->configProvider->isDyeSubEnabled();
    }

    /**
     * Handles updating vendor options and ownership depending on configuration.
     * @throws LocalizedException
     */
    private function updateVendorOptions($quote, $response): void
    {
        if ($this->configProvider->isDyeSubOwnerUpdate()) {
            $updated = $this->documentVendorOwnerUpdater->updateVendorOwnerId($quote, $response);
            if ($updated) {
                $this->canvasQuoteService->updateDyesubVendorOptions($quote, $response);
            }
        } else {
            $this->canvasQuoteService->updateDyesubVendorOptions($quote, $response);
        }
    }

    /**
     * Clear the cart
     */
    private function clearCart()
    {
        try {
            $this->cart->truncate()->save();
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to empty cart: ' . $e->getMessage(),
                ['PHPSESSID' => $this->configProvider->getSessionId()]
            );
        }
    }
}
