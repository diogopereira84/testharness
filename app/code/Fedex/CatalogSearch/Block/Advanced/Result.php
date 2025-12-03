<?php
declare(strict_types=1);

namespace Fedex\CatalogSearch\Block\Advanced;

class Result extends \Magento\CatalogSearch\Block\Advanced\Result
{
    /**
     * Returns advanced search values separated by commas
     */
    public function getSearchValues()
    {
        $searchCriterias = $this->getSearchCriterias();
        $searchCriteriasMerge = array_merge($searchCriterias['left'], $searchCriterias['right']);
        return implode(', ', array_column($searchCriteriasMerge , 'value'));
    }

    /**
     * Excluding last breadcrumb and showing search values on second one
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Search Results | FedEx Office'));
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'search',
                ['label' => __('Search results for \'%1\'', $this->getSearchValues())]
            );
        }
        return $this;
    }

    /**
     * Set order options
     *
     * @return void
     */
    public function setListOrders()
    {
        /* @var $category \Magento\Catalog\Model\Category */
        $category = $this->_catalogLayer->getCurrentCategory();

        $availableOrders = $category->getAvailableSortByOptions();
        unset($availableOrders['position']);
        $availableOrders['relevance'] = __('Relevance');

        $this->getChildBlock('search_result_list')->setAvailableOrders(
            $availableOrders
        )->setDefaultSortBy(
            'relevance'
        );
    }

    /**
     * Get result count.
     *
     * @return mixed
     */
    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = $this->getSearchModel()->getProductCollection()->count();
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }
}
