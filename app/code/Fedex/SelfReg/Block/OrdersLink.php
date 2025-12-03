<?php

namespace Fedex\SelfReg\Block;

use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Commercial\Helper\CommercialHelper;

class OrdersLink extends \Magento\Company\Block\Link\OrdersLink
{
    public $companyContext;
    public $companyManagement;
    public $resource;
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        \Magento\Company\Model\CompanyContext $companyContext,
        \Magento\Company\Api\CompanyManagementInterface $companyManagement,
        public CommercialHelper $commercialHelper,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $companyContext, $companyManagement, $data);
        $this->companyContext = $companyContext;
        $this->companyManagement = $companyManagement;
        if (isset($data['resource'])) {
            $this->resource = $data['resource'];
        }
    }

    public function _toHtml()
    {
        $rolesandpermissionenabled = $this->commercialHelper->isRolePermissionToggleEnable();
        if($rolesandpermissionenabled) {
            if ($this->isVisible()) {
                if (false != $this->getTemplate()) {
                    return parent::_toHtml();
                }
        
                $highlight = '';
        
                if ($this->getIsHighlighted()) {
                    $highlight = ' current';
                }
        
                if ($this->isCurrent()) {
                    $html = '<li class="nav item ss my-orders-link">';
                    $html .= '<strong>'
                        . $this->escapeHtml(__($this->getLabel()))
                        . '</strong>';
                    $html .= '</li>';
                } else {
                    $label =  strtolower(str_replace(' ', '-', $this->getLabel()));
                    $html = '<li class="nav item my-orders-link ' . $highlight . '"><a href="' . $this->escapeHtml($this->getHref()) . '"';
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
            }
            return '';
        } else {
            return parent::_toHtml();
        }
    }
    /**
     * Check visible link for company admin and b2c customers.
     *
     * @return bool
     */
    private function isVisible()
    {
        $company = null;
        if ($this->companyContext->getCustomerId()) {
            $company = $this->companyManagement->getByCustomerId($this->companyContext->getCustomerId());
        }

        return !$company || $this->companyContext->isResourceAllowed($this->resource);
    }
}