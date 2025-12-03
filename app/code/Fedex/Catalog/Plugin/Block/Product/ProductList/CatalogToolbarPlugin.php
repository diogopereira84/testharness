<?php

namespace Fedex\Catalog\Plugin\Block\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Catalog\Model\ProductFactory;
use Fedex\CatalogMvp\Helper\SharedCatalogLiveSearch;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Data\CollectionFactory as MagentoCollectionFactory;

class CatalogToolbarPlugin
{
    protected $collection;
    protected $totalNum;

    public function __construct(
        private readonly DeliveryHelper $deliveryHelper,
        private readonly SdeHelper $sdeHelper,
        private readonly SharedCatalogLiveSearch $liveSearchHelper,
        private readonly MvpHelper $catalogMvpHelper,
        private readonly ToggleConfig $toggleConfig,
        private readonly MagentoCollectionFactory $magentoCollection,
        private readonly ProductFactory $product
    ) {
    }

    /**
     * @param  Toolbar $subject
     * @param  $result
     * @return num
     */
    public function aftergetTotalNum(Toolbar $subject, $result)
    {
        $isEproCustomer = $this->deliveryHelper->isEproCustomer();
        $isSdeStore = $this->sdeHelper->getIsSdeStore();

        if ($isEproCustomer && !$isSdeStore) {
            return $subject->getCollection()->getSize();
        }

        return $result;
    }

   /**
     * @param  Toolbar $subject
     * @param  $result
     * @return num
     */
    public function afterGetLastNum(Toolbar $subject, int $result): int
    {
        if ($this->catalogMvpHelper->shouldApplyCustomPagination()) {
            $customerSession = $this->catalogMvpHelper->getOrCreateCustomerSession();
            $sessionKey = $this->catalogMvpHelper->getSessionPageSizeKey();

            $pageSize = $customerSession?->getData($sessionKey) ?? $this->collection->getPageSize();
            $this->collection->setPageSize($pageSize);

            $currentPage = $this->collection->getCurPage();
            $totalNum = $this->collection->getSize();

            return min($currentPage * $pageSize, $totalNum);
        }

        $collectionCount = min(
            $this->collection->count(),
            $this->collection->getPageSize()
        );

        $lastNum = $this->collection->getPageSize() * ($this->collection->getCurPage() - 1) + $collectionCount;

        return min($lastNum, $this->totalNum);
    }



    /**
     * @param Toolbar $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetFirstNum(Toolbar $subject, mixed $result): mixed
    {
        if (!$this->catalogMvpHelper->shouldApplyCustomPagination()) {
            return $result;
        }

        $collection = $this->collection;
        $customerSession = $this->catalogMvpHelper->getOrCreateCustomerSession();
        $sessionKey = $this->catalogMvpHelper->getSessionPageSizeKey();

        $pageSize = $customerSession?->getData($sessionKey) ?? $collection->getPageSize();

        return $pageSize * ($collection->getCurPage() - 1) + 1;
    }


    
    /**
     * @param  Toolbar $subject
     * @param  $result
     * @return num
     */ 
    public function beforeSetCollection(Toolbar $subject, $collection)
    {
        $data = $this->liveSearchHelper->getProductDeatils();

        if (isset($data['data']['productSearch']['total_count'])) {
            $total_count = $data['data']['productSearch']['total_count'];

            $collection = $this->magentoCollection->create();
            for ($i = 0; $i < $total_count; $i++) {
                $product = $this->product->create();
                $collection->addItem($product);
            }
            $this->collection = $collection;
            $this->totalNum = $total_count;
        }

        return [$collection];
    }

}
