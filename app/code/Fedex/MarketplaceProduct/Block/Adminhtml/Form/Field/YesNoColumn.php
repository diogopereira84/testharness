<?php
/**
 * @copyright Copyright (c) 2024 Fedex.
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\MarketplaceProduct\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class YesNoColumn extends Select
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        private Context $context,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->toOptionArray());
        }

        return parent::_toHtml();
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('No')], ['value' => 1, 'label' => __('Yes')]];
    }
}
