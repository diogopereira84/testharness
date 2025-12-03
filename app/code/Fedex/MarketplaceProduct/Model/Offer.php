<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Mirakl\Connector\Model\Offer as MiraklOffer;
use Fedex\MarketplaceProduct\Api\Data\OfferInterface;

class Offer extends MiraklOffer implements OfferInterface
{
    /**
     * @inheritDoc
     */
    public function getId(): string|null
    {
        return $this->getData(self::OFFER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($offerId)
    {
        $this->setData(self::OFFER_ID, $offerId);
        return $this;
    }
}
