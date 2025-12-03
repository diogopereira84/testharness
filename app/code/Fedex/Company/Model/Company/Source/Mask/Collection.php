<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Source\Mask;

use InvalidArgumentException;
use Magento\Framework\Data\Collection as MagentoCollection;
use Fedex\Company\Model\Company\Source\OptionInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;

class Collection extends MagentoCollection
{
    /**
     * Option label key
     */
    private const LABEL = 'label';

    /**
     * Option value key
     */
    private const VALUE = 'value';

    /**
     * Constructor
     *
     * Generate the option collection based on di.xml
     *
     * @param EntityFactoryInterface $entityFactory
     * @param array $options
     */
    public function __construct(
        readonly EntityFactoryInterface $entityFactory,
        readonly array $options = []
    ) {
        parent::__construct($entityFactory);
        foreach ($options as $option) {
            if (gettype($option) != "object") {
                throw new InvalidArgumentException(
                    sprintf(
                        'Instance of the %s is expected, got non object instead.',
                        OptionInterface::class
                    )
                );
            }
            if (!$option instanceof OptionInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Instance of the %s is expected, got %s instead.',
                        OptionInterface::class,
                        get_class($option),
                    )
                );
            }
        }
        $this->_items = $this->options;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this as $item) {
            $options[] = [
                self::LABEL => $item->getData(self::LABEL),
                self::VALUE => $item->getData(self::VALUE)
            ];
        }
        return $options;
    }
}
