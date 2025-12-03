<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Model\Company\Source\Mask;

use Fedex\Company\Model\Company\Source\OptionInterface;
use Magento\Framework\DataObject;

class NoOption extends DataObject implements OptionInterface
{
    /**
     * Option label key
     */
    private const LABEL_KEY = 'label';

    /**
     * Option label
     */
    private const LABEL = 'No';

    /**
     * Option value key
     */
    private const VALUE_KEY = 'value';

    /**
     * Option value
     */
    private const VALUE = null;

    /**
     * Constructor
     *
     * By default, is looking for first argument
     * as array and assigns it as object attributes
     * This behavior may change in child classes
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $data = array_merge_recursive(
            [
                self::LABEL_KEY => self::LABEL,
                self::VALUE_KEY => self::VALUE,
            ],
            $data
        );
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->getData(self::LABEL_KEY) ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getValue(): ?string
    {
        return $this->getData(self::VALUE_KEY);
    }
}
