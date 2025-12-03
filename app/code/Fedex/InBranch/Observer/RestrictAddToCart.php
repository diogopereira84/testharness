<?php
namespace Fedex\InBranch\Observer;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Message\ManagerInterface;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Fedex\FedexCsp\Stdlib\Cookie\PhpCookieManager;

class RestrictAddToCart implements ObserverInterface
{
    /**
     * RestrictAddToCart constructor.
     *
     * @param ProductRepository $productRepository
     * @param ManagerInterface $messageManager
     * @param InBranchValidation $inBranchValidation
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param PhpCookieManager $cookieManager
     * @param RedirectInterface $redirect
     */
    public function __construct(
        private ProductRepository $productRepository,
        private ManagerInterface $messageManager,
        private InBranchValidation $inBranchValidation,
        private CookieMetadataFactory $cookieMetadataFactory,
        private PhpCookieManager $cookieManager,
        private RedirectInterface $redirect
    )
    {
    }

    /**
     * Before add to cart check isinbranch Product Exist
     *
     * @param EventObserver $observer
     * @return $this|void
     */
    public function execute(EventObserver $observer)
    {
        try {
            $productId = $observer->getRequest()->getParam('product');
            $product = $this->productRepository->getById($productId);
             //Inbranch Implementation
            $isInBranchProductExist = $this->inBranchValidation->isInBranchValid($product);
            if ($isInBranchProductExist) {
                $isAjaxRequest =  $observer->getRequest()->getParam('isAjax');
                 $observer->getRequest()->setParam('product', false);
                if (!$isAjaxRequest) {
                          setcookie("isInBranchProductExist", true, time() + (86400 * 30), "/");
                          $observer->getRequest()->setParam('return_url', $this->redirect->getRefererUrl());
                }
                  $observer->getRequest()->setParam('inBranchProductExist', true);
                  return $this;
            }
            //Inbranch Implementation
            return $this;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
