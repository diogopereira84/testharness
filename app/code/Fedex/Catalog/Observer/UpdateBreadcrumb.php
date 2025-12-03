<?php

namespace Fedex\Catalog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Fedex\Catalog\Helper\Breadcrumbs as BreadcrumbsHelper;

class UpdateBreadcrumb implements ObserverInterface
{

    /**
     * UpdateBreadcrumb constructor.
     * @param BreadcrumbsHelper $helper
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        public BreadcrumbsHelper $helper,
        public TypeListInterface $cacheTypeList
    )
    {
    }

    /**
     * Execute method
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent()->getData();
        $pageFound = false;
        if ((isset($event['status']) && $event['status'] === 'success') &&
            (isset($event['title']) && $event['title'] !== '')) {
            $pageJson = $this->helper->getControlJson();
            if ($pageJson) {
                $this->setConfigJson($pageJson, $pageFound, $event);
            }
        }
    }

    /**
     * @param string $pageJson
     * @param boolean $pageFound
     * @param array $event
     */
    public function setConfigJson($pageJson, $pageFound, $event)
    {
        $configJson = json_decode($pageJson, true);
        foreach ($configJson as $key => $value) {
            if ($value['label'] === $event['title']) {
                unset($configJson[$key]);
                $pageFound = true;
                break;
            }
        }
        if ($pageFound) {
            $configJson = array_merge($configJson);
            if (empty($configJson)) {
                $this->helper->setControlJson('');
            } else {
                $this->helper->setControlJson(json_encode($configJson));
            }
            $this->cacheTypeList->cleanType('config');
        }
    }
}
