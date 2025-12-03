<?php
/**
 * @category  Fedex
 * @package   Fedex_Delivery
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Delivery\Model\ShippingMessage;

use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Magento\Quote\Api\Data\CartInterface;

interface TransportInterface
{
    /**
     * @return ?string
     */
    public function getStrategy(): ?string;

    /**
     * @param string $strategy
     * @return TransportInterface
     */
    public function setStrategy(string $strategy): TransportInterface;

    /**
     * @return CartInterface
     */
    public function getCart(): CartInterface;

    /**
     * @param CartInterface $cart
     * @return TransportInterface
     */
    public function setCart(CartInterface $cart): TransportInterface;

    /**
     * @return ?RateInterface
     */
    public function getFXORate(): ?RateInterface;

    /**
     * @param RateInterface $fXORate
     * @return TransportInterface
     */
    public function setFXORate(RateInterface $fXORate): TransportInterface;

    /**
     * @return ?RateQuoteInterface
     */
    public function getFXORateQuote(): ?RateQuoteInterface;

    /**
     * @param RateQuoteInterface $fXORateQuote
     * @return TransportInterface
     */
    public function setFXORateQuote(RateQuoteInterface $fXORateQuote): TransportInterface;

    /**
     * @return ?AlertCollectionInterface
     */
    public function getFXORateAlert(): ?AlertCollectionInterface;

    /**
     * @param AlertCollectionInterface $fXORateAlert
     * @return TransportInterface
     */
    public function setFXORateAlert(AlertCollectionInterface $fXORateAlert): TransportInterface;
}
