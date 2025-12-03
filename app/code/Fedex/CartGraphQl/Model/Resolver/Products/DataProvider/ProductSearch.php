<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch\ProductCollectionSearchCriteriaBuilder;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

class ProductSearch extends \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch
{
    private SearchResultApplierFactory $searchResultApplierFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionPreProcessor
     * @param CollectionPostProcessorInterface $collectionPostProcessor
     * @param SearchResultApplierFactory $searchResultsApplierFactory
     * @param ProductCollectionSearchCriteriaBuilder $searchCriteriaBuilder
     * @param Visibility $catalogProductVisibility
     */
    public function __construct(
        private CollectionFactory $collectionFactory,
        private ProductSearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionPreProcessor,
        private CollectionPostProcessorInterface $collectionPostProcessor,
        SearchResultApplierFactory $searchResultsApplierFactory,
        private ProductCollectionSearchCriteriaBuilder $searchCriteriaBuilder,
        private Visibility $catalogProductVisibility
    ) {
        $this->searchResultApplierFactory = $searchResultsApplierFactory;

        parent::__construct(
            $this->collectionFactory,
            $this->searchResultsFactory,
            $this->collectionPreProcessor,
            $this->collectionPostProcessor,
            $this->searchResultApplierFactory,
            $this->searchCriteriaBuilder,
            $this->catalogProductVisibility
        );
    }

    /**
     * Get list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchResultInterface $searchResult
     * @param array $attributes
     * @param ContextInterface|null $context
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        SearchResultInterface $searchResult,
        array $attributes = [],
        ContextInterface $context = null
    ): SearchResultsInterface {
        $productIds = [];
        foreach ($searchResult->getItems() as $item) {
            $productIds[] = $item->getId();
        }

        $collection = $this->collectionFactory->create();
        $collection->addIdFilter($productIds);
        $collection->addAttributeToSelect('*');
        $collection->getSelect()->order(
            new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $productIds) . ')')
        );

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
