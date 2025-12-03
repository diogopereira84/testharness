<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

/* B-1060632 - RT-ECVS- View Order Details
Remove Invoice Section  */
namespace Fedex\Orderhistory\Block\Order;

class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Framework\Registry $registry
     * @param \Fedex\Orderhistory\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Framework\Registry $registry,
        protected \Fedex\Orderhistory\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->_registry = $registry;
    }

     /**
      * @inheritdoc
      * @codeCoverageIgnore
      * @return string
      */
    protected function _toHtml(): string
    {
        $methodExist = null;
        if ($this->getOrder()) {
            $methodExist = method_exists($this->getOrder(), 'has' . $this->getKey());
        }

        if ($this->hasKey()
            && $methodExist
            && !$this->getOrder()->{'has' . $this->getKey()}()
        ) {
            return '';
        }
        if (
            ($this->helper->isModuleEnabled() == true && $this->hasKey() == 'Invoices') ||
            ($this->helper->isPrintReceiptRetail() == true)
        ) {
            return '';
        }
        return parent::_toHtml();
    }
}
