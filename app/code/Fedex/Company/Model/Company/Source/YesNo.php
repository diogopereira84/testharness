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

class YesNo implements OptionSourceInterface
{
    /**
     * Options in "key-value" format
     */
    private const OPTIONS = [
        [
            'value' => 1,
            'label' => 'Yes'
        ],
        [
            'value' => 0,
            'label' => 'No'
        ]
    ];

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return self::OPTIONS;
    }
}
