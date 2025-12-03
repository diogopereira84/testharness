<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\FXOPricing\Api\Data\RateQuoteInterface;
use Fedex\FXOPricing\Api\RateQuoteBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateQuoteDetailCollectionInterfaceFactory as DetailCollectionFactory;
use Fedex\FXOPricing\Api\Data\RateQuoteInterfaceFactory;
use Fedex\FXOPricing\Api\RateQuoteDetailBuilderInterface;

class RateQuoteBuilder implements RateQuoteBuilderInterface
{
    /**
     * Currency key
     */
    private const CURRENCY = 'currency';

    /**
     * RateQuote details key
     */
    private const RATE_DETAILS = 'rateQuoteDetails';

    public function __construct(
        private readonly RateQuoteInterfaceFactory $rateFactory,
        private readonly RateQuoteDetailBuilderInterface $detailBuilder,
        private readonly DetailCollectionFactory $detailCollectionFactory
    ) {
    }

    public function build(array $data = []): RateQuoteInterface
    {
        $rate = $this->rateFactory->create();

        if (isset($data[self::CURRENCY])) {
            $rate->setCurrency($data[self::CURRENCY]);
        }

        $rateDetailCollection = $this->detailCollectionFactory->create();
        if (isset($data[self::RATE_DETAILS]) && is_array($data[self::RATE_DETAILS])) {
            foreach ($data[self::RATE_DETAILS] as $detailData) {
                $detail = $this->detailBuilder->build($detailData);
                $rateDetailCollection->addItem($detail);
            }
        }
        $rate->setDetails($rateDetailCollection);

        return $rate;
    }
}
