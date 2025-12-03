<?php
/**
 * @copyright Copyright (c) 2024 Fedex.
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\MarketplaceProduct\Block\Adminhtml\Form\Field;

use Fedex\Company\Model\Config\Source\Companies;
use Magento\Company\Model\Company;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Mirakl\Mci\Helper\Data as MciHelper;

class MiraklAttributesColumn extends Select
{
    /**
     * @param Context $context
     * @param MciHelper $companies
     * @param array $data
     */
    public function __construct(
        private Context $context,
        private MciHelper $mciHelper,
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
            $this->setOptions($this->generateOptions());
        }

        return parent::_toHtml();
    }

    /**
     * @return array[]
     */
    public function generateOptions(): array
    {
        $miraklAttribute = [['value' => '', 'label' => __('Select Mirakl Attribute')]];
        /** @var Company $company */
        foreach ($this->mciHelper->getImagesAttributes() as $attributeCode => $attribute) {
            $miraklAttribute[] = [
                'value' => $attributeCode,
                'label' => $attribute->getFrontend()->getLabel()
            ];
        }

        return $miraklAttribute;
    }
}
