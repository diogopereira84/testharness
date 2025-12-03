<?php
/**
 * @copyright Copyright (c) 2023 Fedex.
 * @author    Iago Lima <iago.lima.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\Company\Block\Adminhtml\Form\Field;

use Fedex\Company\Model\Config\Source\Companies;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class CompanyColumn
 *
 * @package Fedex\Company\Block\Adminhtml\Form\Field
 */
class CompanyColumn extends Select
{
    /**
     * CompanyColumn constructor
     *
     * @param Context $context
     * @param Companies $companies
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Companies $companies,
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
            $this->setOptions($this->companies->toOptionArray());
        }

        return parent::_toHtml();
    }
}
