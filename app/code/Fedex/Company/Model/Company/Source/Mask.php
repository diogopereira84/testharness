<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Mask implements OptionSourceInterface
{
    public function __construct(
        private readonly Mask\Collection $collection
    ) {
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return $this->collection->toOptionArray();
    }
}
