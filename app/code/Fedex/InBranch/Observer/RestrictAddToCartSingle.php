<?php

namespace Fedex\InBranch\Observer;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Message\ManagerInterface;
use Fedex\InBranch\Model\InBranchValidation;

class RestrictAddToCartSingle implements ObserverInterface
{
    /**
     * @param ProductRepository $productRepository
     * @param ManagerInterface $messageManager
     * @param InBranchValidation $inBranchValidation
     * @param RedirectInterface $redirect
     */
    public function __construct(
        private ProductRepository  $productRepository,
        private ManagerInterface   $messageManager,
        private InBranchValidation $inBranchValidation,
        private RedirectInterface  $redirect
    )
    {
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer): static
    {
        try {
            $request = $observer->getData('request');
            $productId = $request->getParam('id');
            $product = $this->productRepository->getById($productId);
            $isInBranchProductExist = $this->inBranchValidation->isInBranchValid($product);
            if ($isInBranchProductExist) {
                $isAjaxRequest = $request->getParam('isAjax');
                $request->setParam('id', false);
                if (!$isAjaxRequest) {
                    setcookie("isInBranchProductExist", true, time() + (86400 * 30), "/");
                    $request->setParam('return_url', $this->redirect->getRefererUrl());
                }
                $request->setParam('inBranchProductExist', true);
                return $this;
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this;
    }
}
