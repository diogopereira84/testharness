<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Observer;

use Fedex\SaaSCommon\Api\ConfigInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\SaaSCommon\Model\Queue\Publisher;

class ProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @param Publisher $publisher
     * @param ConfigInterface $config
     */
    public function __construct(
        protected Publisher $publisher,
        protected ConfigInterface $config,
        protected AllowedCustomerGroupsRequestInterface $request
    ) {
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if ($this->config->isTigerD200529Enabled() && $product && $product->getId()) {
            $this->request->setEntityId((int)$product->getId());
            $this->request->setEntityType(Product::ENTITY);
            $this->publisher->publish($this->request);
        }
    }
}
