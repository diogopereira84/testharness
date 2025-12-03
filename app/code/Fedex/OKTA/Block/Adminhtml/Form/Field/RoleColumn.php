<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);
namespace Fedex\OKTA\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Fedex\OKTA\Model\Config\Source\Role as SourceRole;

/**
 * Class RoleColumn
 *
 * @package Fedex\OKTA\Block\Adminhtml\Form\Field
 * @method setName(string $value)
 */
class RoleColumn extends Select
{
    /**
     * RoleColumn constructor
     *
     * @param Context $context
     * @param SourceRole $sourceRole
     * @param array $data
     */
    public function __construct(
        Context $context,
        private SourceRole $sourceRole,
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
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * @return string[][]
     */
    private function getSourceOptions(): array
    {
        return $this->sourceRole->getAllOptions();
    }
}
