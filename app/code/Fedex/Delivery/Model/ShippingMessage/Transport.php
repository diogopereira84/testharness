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
use Magento\Quote\Api\Data\CartInterface;
use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteInterface;

class Transport implements TransportInterface
{
    private ?string $strategy = null;

    public function __construct(
        private CartInterface $cart,
        private RateInterface $fXORate,
        private RateQuoteInterface $fXORateQuote,
        private AlertCollectionInterface $fXORateAlert,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getStrategy(): ?string
    {
        return $this->strategy;
    }

    /**
     * @inheritDoc
     */
    public function setStrategy(string $strategy): TransportInterface
    {
        $this->strategy = $strategy;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCart(): CartInterface
    {
        return $this->cart;
    }

    /**
     * @inheritDoc
     */
    public function setCart(CartInterface $cart): TransportInterface
    {
        $this->cart = $cart;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFXORate(): ?RateInterface
    {
        return $this->fXORate;
    }

    /**
     * @inheritDoc
     */
    public function setFXORate(RateInterface $fXORate): TransportInterface
    {
        $this->fXORate = $fXORate;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFXORateQuote(): ?RateQuoteInterface
    {
        return $this->fXORateQuote;
    }

    /**
     * @inheritDoc
     */
    public function setFXORateQuote(RateQuoteInterface $fXORateQuote): TransportInterface
    {
        $this->fXORateQuote = $fXORateQuote;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFXORateAlert(): ?AlertCollectionInterface
    {
        return $this->fXORateAlert;
    }

    /**
     * @inheritDoc
     */
    public function setFXORateAlert(AlertCollectionInterface $fXORateAlert): TransportInterface
    {
        $this->fXORateAlert = $fXORateAlert;
        return $this;
    }
}
