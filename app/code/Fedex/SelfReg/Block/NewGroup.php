<?php

namespace Fedex\SelfReg\Block;


use Magento\Framework\View\Element\Template\Context;
use Magento\Company\Api\CompanyManagementInterface;

class NewGroup extends \Magento\Framework\View\Element\Template
{

    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
    ) {
        parent::__construct($context);
    }

}

