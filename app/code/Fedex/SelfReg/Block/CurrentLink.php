<?php

namespace Fedex\SelfReg\Block;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Commercial\Helper\CommercialHelper;

class CurrentLink extends \Magento\Customer\Block\Account\SortLink
{
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        public CommercialHelper $commercialHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    public function _toHtml()
    {
        $rolesandpermissionenabled = $this->commercialHelper->isRolePermissionToggleEnable();
        if($rolesandpermissionenabled) {
            if (false != $this->getTemplate()) {
                return parent::_toHtml();
            }
    
            $highlight = '';
    
            if ($this->getIsHighlighted()) {
                $highlight = ' current';
            }
    
            if ($this->isCurrent()) {
                $html = '<li class="nav item">';
                $html .= '<strong>'
                    . $this->escapeHtml(__($this->getLabel()))
                    . '</strong>';
                $html .= '</li>';
            } else {
                $label =  strtolower(str_replace(' ', '-', $this->getLabel()));
                $html = '<li class="nav item' . $highlight . '"><a href="' . $this->escapeHtml($this->getHref()) . '"';
                $html .= $this->getTitle()
                    ? ' title="' . $this->escapeHtml(__($this->getTitle())) . '"'
                    : '';
                $html .= $this->getAttributesHtml() . ' data-test-id="E-404291-B-2010873-TK-3436949-manage-user-action-'.$label.'">';
    
                if ($this->getIsHighlighted()) {
                    $html .= '<strong>';
                }
    
                $html .= $this->escapeHtml(__($this->getLabel()));
    
                if ($this->getIsHighlighted()) {
                    $html .= '</strong>';
                }
    
                $html .= '</a></li>';
            }
    
            return $html;
        } else {
            return parent::_toHtml();
        }
    }
}