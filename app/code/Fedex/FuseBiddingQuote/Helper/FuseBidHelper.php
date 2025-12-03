<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

/**
 * FuseBidding module helper class
 */
class FuseBidHelper extends AbstractHelper
{
    public const CONFIG_BASE_PATH = 'fedex/upload_to_quote_config/';

    public const CONFIG_SSO_BASE_PATH = 'sso/general/';

    /**
     * FuseBidHelper Constructor
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ToggleConfig $toggleConfig
     * @param CartFactory $cartFactory
     * @param QuoteFactory $quoteFactory
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        protected ToggleConfig $toggleConfig,
        protected CartFactory $cartFactory,
        protected QuoteFactory $quoteFactory,
        protected CheckoutSession $checkoutSession,
        protected LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Validate if the Fuse Bidding toggle is enabled.
     *
     * @throws GraphQlInputException
     */
    public function isFuseBidGloballyEnabled()
    {
        if ($this->toggleConfig->getToggleConfigValue('fuse_bidding_quote_retail_commercial')) {

            return true;
        }
    }

    /**
     * To get the Upload To Quote Config Value
     *
     * @param string $key
     * @param int|null $storeId
     * @return bool|string
     */
    public function getUploadToQuoteConfigValue($key, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_BASE_PATH . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * To get the SSO Config Value
     *
     * @param string $key
     * @param int|null $storeId
     * @return bool|string
     */
    public function getSsoConfigValue($key, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_SSO_BASE_PATH . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Deactivate quote
     *
     * @return void
     */
    public function deactivateQuote()
    {
        $currentQuote = $this->cartFactory->create()->getQuote();
        if ($currentQuote->getIsBid()) {
            $currentQuote->setIsActive(0);
            $currentQuote->save();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .
                ' Fusebid Quote is deactivated with quote id '.$currentQuote->getId().
                ' and is_bid flag as '.$currentQuote->getIsBid()
            );
            $newQuote = $this->quoteFactory->create();
            $newQuote->save();
            $this->checkoutSession->replaceQuote($newQuote);
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .' Quote is replaced with new quote id : '.$newQuote->getId()
            );
        }
    }

    /**
     * Check rate quote details api toggle is enable or not
     *
     * @return boolean
     */
    public function isRateQuoteDetailApiEnabed()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_call_rate_quote_details_api');
    }

    /**
     * Check send sourceRetailLocationId toggle is enable or not
     *
     * @return boolean
     */
    public function isSendRetailLocationIdEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_send_source_retail_location_id');
    }

    /**
     * To check Online Checkout for Fusebidding Quote toggle is enable or not
     *
     * @return boolean
     */
    public function isBidCheckoutEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_fuse_bid_checkout_enabled');
    }

    /**
     * Fixed the contact information update issue
     *
     * @return boolean
     */
    public function isContactInfoFix()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_d208767_d209651_enable');
    }

    /**
     * B-2388454 Team Member Info Update
     *
     * @return boolean
     */
    public function isToggleTeamMemberInfoEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('mazegeek_b2388454_enable');
    }
    /*
     * @return bool|int
     */
    public function isToggleD215974Enabled()
    {
        return $this->toggleConfig->getToggleConfigValue('tiger_d215974');
    }
}
