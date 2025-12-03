<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model;

use Fedex\FujitsuReceipt\Model\FujitsuReceipt;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SendFujitsuReceiptConfirmationEmail
{
    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param FujitsuReceipt $fujitsuReceipt
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private LoggerInterface    $logger,
        private FujitsuReceipt     $fujitsuReceipt,
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Send Fujitsu receipt confirmation email.
     *
     * @param string $message
     * @return void
     */
    public function execute(string $message)
    {
        $newFijtsuToggle = $this->toggleConfig->getToggleConfigValue('new_fujitsu_receipt_approach');
        if (!$newFijtsuToggle) {
            $this->logger->info('Send fujitsu receipt confirmation email data: '.$message);
            $this->fujitsuReceipt->sendFujitsuReceiptConfirmationEmail(json_decode($message, true));
        }
    }
}
