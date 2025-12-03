<?php
/**
 * @copyright Copyright (c) 2024 Fedex.
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\MarketplaceProduct\Block\Adminhtml\Form\Field;

use Fedex\Company\Model\Config\Source\Companies;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class ImageAttributesColumn extends Select
{
    /**
     * @var Companies
     */
    private Companies $companies;

    /**
     * @param Context $context
     * @param Product $product
     * @param array $data
     */
    public function __construct(
        private Context $context,
        private Product $product,
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
        $value = $value . '[]';
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
            $this->setOptions($this->generateOptions());
        }
        $optionsSize = count($this->getOptions());
        $this->setExtraParams('multiple="multiple" style="width: 280px;" size="' . $optionsSize . '"');
        $this->setClass('select multiselect admin__control-multiselect');
        return parent::_toHtml();
    }

    /**
     * @return array[]
     */
    public function generateOptions(): array
    {
        $imageAttributes = [];
        $this->product->setTypeId('simple');
        foreach ($this->product->getMediaAttributes() as $attributeCode => $attribute) {
            $imageAttributes[] = [
                'value' => $attributeCode,
                'label' => $attribute->getFrontend()->getLabel()
            ];
        }

        return $imageAttributes;
    }
}
