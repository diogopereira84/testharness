<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class LeftMenu extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor
     * @param Context  $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Get left menu
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $blockContent = $resultPage->getLayout()
            ->createBlock(\Fedex\CustomizedMegamenu\Block\Html\CategoryList::class)
            ->setTemplate('Fedex_CustomizedMegamenu::layerednavigation/category.phtml')
            ->toHtml();

        return $this->getResponse()->setBody($blockContent);
    }
}
