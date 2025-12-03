<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2025 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Plugin;

use Magento\Sales\Block\Adminhtml\Order\View\Info as OrderViewInfo;
use Magento\Sales\Model\Order\Address;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressEvaluator;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressProvider;
use Fedex\MarketplaceAdmin\Service\Address\MiraklShippingAddressFormatter;

class AdminOrderViewInfoPlugin
{
    /**
     * @param MiraklShippingAddressEvaluator $evaluator
     * @param MiraklShippingAddressProvider $provider
     * @param MiraklShippingAddressFormatter $formatter
     */
    public function __construct(
        private MiraklShippingAddressEvaluator $evaluator,
        private MiraklShippingAddressProvider $provider,
        private MiraklShippingAddressFormatter $formatter
    ) {}

    /**
     * @param OrderViewInfo $subject
     * @param string $result
     * @param Address $address
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetFormattedAddress(OrderViewInfo $subject, string $result, Address $address): string
    {
        $order = $subject->getOrder();

        if (!$this->evaluator->shouldOverride($order, $address)) {
            return $result;
        }

        $miraklData = $this->provider->getAddress($order);
        if ($miraklData === null) {
            return $result;
        }

        return $this->formatter->format($miraklData);
    }
}
