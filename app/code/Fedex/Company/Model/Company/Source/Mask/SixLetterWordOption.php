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

class SixLetterWordOption extends DataObject implements OptionInterface
{
    /**
     * Option label key
     */
    private const LABEL_KEY = 'label';

    /**
     * Option label
     */
    private const LABEL = '6 Letter Word';

    /**
     * Option value key
     */
    private const VALUE_KEY = 'value';

    /**
     * Option value
     */
    private const VALUE = 'validate-alpha-6';

    /**
     * CSS class key
     */
    private const CSS_CLASS_KEY = 'css_class';

    /**
     * CSS class value
     */
    private const CSS_CLASS = 'minimum-length-6 maximum-length-6';

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
                self::CSS_CLASS_KEY => self::CSS_CLASS,
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

    /**
     * Provide the CSS class responsible for validation
     * to be used in the frontend css attribute
     *
     * @return string
     */
    public function getCssClass(): string
    {
        return $this->getData(self::CSS_CLASS_KEY);
    }
}
