<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\FXOPricing\Api\Data\RateInterface;
use Fedex\FXOPricing\Api\RateBuilderInterface;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterfaceFactory;
use Fedex\FXOPricing\Api\Data\RateInterfaceFactory;
use Fedex\FXOPricing\Api\RateDetailBuilderInterface;

class RateBuilder implements RateBuilderInterface
{
    /**
     * Currency key
     */
    private const CURRENCY = 'currency';

    /**
     * Rate details key
     */
    private const RATE_DETAILS = 'rateDetails';

    public function __construct(
        private readonly RateInterfaceFactory $rateFactory,
        private readonly RateDetailBuilderInterface $detailBuilder,
        private readonly RateDetailCollectionInterfaceFactory $detailCollectionFactory
    ) {
    }

    public function build(array $data = []): RateInterface
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
