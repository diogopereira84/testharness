<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller for obtaining stores suggestions by query.
 */
class Topmenu extends Action
{
    /**
     * constructor function
     *
     * @param Context $context
     * @return void
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        private PageFactory $resultPageFactory,
        private JsonFactory $resultJsonFactory,
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
    }
    /**
     * Get Store/Store view list
     *
     * @return Json
     */
    public function execute()
    {
        $html = '';
        $result = $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();
        $html = $resultPage->getLayout()
                ->createBlock('Magento\Theme\Block\Html\Topmenu')
                ->setTemplate('Magento_Theme::html/topmenu.phtml')
                ->toHtml();

       /** @var Raw $rawResult */
       $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);
       return $rawResult->setContents($html);
    }
}
