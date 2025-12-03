<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplacePunchout
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ExpiredProducts
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductInfo $productInfo
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private CheckoutSession $checkoutSession,
        private ProductInfo $productInfo,
        private TimezoneInterface $timezone
    ) {
    }

    /**
     * @return array
     * @throws Exception
     */
    public function execute(): array
    {
        $quote = $this->checkoutSession->getQuote();
        $expiredIds = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getMiraklOfferId() && $item->getAdditionalData()) {
                $punchoutEnabled = true;
                $additionalData = json_decode($item->getAdditionalData());
                $expiredData = false;
                if (isset($additionalData->punchout_enabled)) {
                    $punchoutEnabled = (bool)$additionalData->punchout_enabled;
                }
                if (!$punchoutEnabled) {
                    continue;
                }
                if (isset($additionalData->expire)) {
                    $date = $this->timezone->date($item->getCreatedAt());
                    $date->modify("+".json_decode($item->getAdditionalData())->expire." day");
                    $itemExpiryDate = $this->timezone->convertConfigTimeToUtc($date);
                    $currentDate = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
                    $expiredData = $currentDate >= $itemExpiryDate;
                }

                if ($expiredData || !$additionalData->supplierPartAuxiliaryID ||
                    !$this->productInfo->execute($additionalData->supplierPartAuxiliaryID, $item->getSku())) {
                    $expiredIds[] = $item->getId();
                }
            }
        }
        return $expiredIds;
    }
}
