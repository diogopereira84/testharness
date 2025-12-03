<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Observer;

use Fedex\SaaSCommon\Api\ConfigInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Fedex\SaaSCommon\Model\Queue\Publisher;

class CategorySaveAfterObserver implements ObserverInterface
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
        $category = $observer->getEvent()->getCategory();
        if ($this->config->isTigerD200529Enabled() && $category && $category->getId()) {
            $this->request->setEntityId((int)$category->getId());
            $this->request->setEntityType(Category::ENTITY);
            $this->publisher->publish($this->request);
        }
    }
}
