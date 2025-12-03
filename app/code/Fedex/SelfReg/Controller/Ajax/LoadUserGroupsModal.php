<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Controller\Ajax;

use Dompdf\FrameDecorator\Block;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Request\Http;

class LoadUserGroupsModal implements ActionInterface
{
    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        protected Context $context,
        private PageFactory $pageFactory,
        private Http $request
    ) {
        $this->request = $request;
    }

    /**
     * Execute method
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $categoryId = $this->request->getParam('categoryId');
        if(!$categoryId) {
            return 'invalid category id';
        }
        
        $block = $resultPage->getLayout()->createBlock('Fedex\SelfReg\Block\UserGroupsList');
        $block->setData('categoryId', (int) $categoryId);
        $block->setTemplate('Magento_Catalog::product/view/usergroupsmodal.phtml');

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->context->getResultFactory()->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        $resultRaw->setContents($block->toHtml());
        return $resultRaw;
    }
}
