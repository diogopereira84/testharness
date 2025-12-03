<?php
/**
 * @category  Fedex
 * @package   Fedex_MarketplaceCheckout
 * @author    Niket Kanoi <niket.kanoi.osv@fedex.com>
 * @copyright 2023 FedEx
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Controller\Adminhtml\Shop;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;

class Edit extends Action
{
    /**
     * @param Context $context
     * @param ResultFactory $resultFactory
     * @param ShopRepositoryInterface $shopRepository
     */
    public function __construct(
        Context                 $context,
        ResultFactory           $resultFactory,
        private ShopRepositoryInterface $shopRepository
    ) {
        $this->resultFactory = $resultFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $storeName = '';
        if ($id) {
            $shop = $this->shopRepository->getById((int) $id);
            $storeName = $shop->getName();
        }
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__('Update Shipping Methods for ' . $storeName));
        return $resultPage;
    }
}
