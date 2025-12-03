<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Controller\Fail;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;
    private \Magento\Framework\View\Result\PageFactory $_pageFactory;

    /**
     * Constructor
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     */

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        private TypeListInterface $cacheTypeList,
        private Pool $cacheFrontendPool
    ) {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * Execute method
     *
     * @codeCoverageIgnore
     * B-1320022 - WLGN integration for selfReg customer
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        return $this->_pageFactory->create();
    }
}
