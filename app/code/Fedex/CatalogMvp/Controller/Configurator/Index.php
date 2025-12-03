<?php

namespace Fedex\CatalogMvp\Controller\Configurator;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductRepository;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    
    /**
     * @param Context $context
     * @param PageFactory $_pageFactory
     * @param RedirectInterface $redirect
     * @param ResultFactory $resultFactory
     * @param SearchCriteriaBuilder $resultFactory
     * @param ProductRepository $resultFactory
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        protected RedirectInterface $redirect,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected ProductRepository $productRepository
    )
    {
        $this->_pageFactory = $pageFactory;
       
        return parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getParams();
        if(isset($data['undefined'])) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->redirect->getRefererUrl());
            return $resultRedirect;
        }
        if(isset($data['sku'])) {
            $productSku = $data['sku'];
            $searchCriteria = $this->searchCriteriaBuilder->addFilter("sku", $productSku,'eq')->create();
            $products = $this->productRepository->getList($searchCriteria);
            $items = $products->getItems();
            if (count($items) == 0) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->redirect->getRefererUrl());
                return $resultRedirect;
            }
        }
        return $this->_pageFactory->create();
    }

}
