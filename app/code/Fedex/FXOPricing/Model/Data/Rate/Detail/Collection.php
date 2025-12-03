<?php
/**
 * @category  Fedex
 * @package   Fedex_FXOPricing
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model\Data\Rate\Detail;

use Fedex\FXOPricing\Api\Data\RateDetailInterface;
use Fedex\FXOPricing\Api\Data\RateDetailCollectionInterface;

class Collection extends \Fedex\Base\Model\Data\Collection implements RateDetailCollectionInterface
{
}
