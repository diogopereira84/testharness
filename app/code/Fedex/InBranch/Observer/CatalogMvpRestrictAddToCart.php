<?php
/**
 * @category  Fedex
 * @package   Fedex_InBranch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\InBranch\Observer;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Message\ManagerInterface;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Fedex\FedexCsp\Stdlib\Cookie\PhpCookieManager;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class CatalogMvpRestrictAddToCart implements ObserverInterface
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
        private ProductRepository     $productRepository,
        private ManagerInterface      $messageManager,
        private InBranchValidation    $inBranchValidation,
        private CookieMetadataFactory $cookieMetadataFactory,
        private PhpCookieManager      $cookieManager,
        private readonly CatalogMvp   $catalogMvpHelper,
        private RedirectInterface     $redirect
    ) {
        $this->productRepository = $productRepository;
        $this->messageManager = $messageManager;
        $this->inBranchValidation = $inBranchValidation;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->redirect = $redirect;
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
            if ($this->catalogMvpHelper->isMvpSharedCatalogEnable()) {
                $request = $observer->getData('request');
                $productIds = $request->getParam('id');
                if (!empty($productIds)) {
                    foreach ($productIds as $key => $productId) {
                        $product = $this->productRepository->getById($productId);
                        $isInBranchProductExist = $this->inBranchValidation->isInBranchValid($product);
                        if ($isInBranchProductExist) {
                            $isAjaxRequest =  $observer->getRequest()->getParam('isAjax');
                            $observer->getRequest()->setParam('id', false);
                            if (!$isAjaxRequest) {
                                setcookie("isInBranchProductExist", true, time() + (86400 * 30), "/");
                                $observer->getRequest()->setParam('return_url', $this->redirect->getRefererUrl());
                            }
                            $observer->getRequest()->setParam('inBranchProductExist', true);
                            return $this;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
