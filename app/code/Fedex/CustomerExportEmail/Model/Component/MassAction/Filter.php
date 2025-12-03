<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerExportEmail\Model\Component\MassAction;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface;

/**
 * Filter component.
 *
 * @api
 * @since 100.0.2
 */
class Filter extends \Magento\Ui\Component\MassAction\Filter
{
    const SELECTED_PARAM = 'selected';

    const EXCLUDED_PARAM = 'excluded';

    const CUSTOMEREXPORT_TOGGLE = 'customerexporttoggle';

    /**
     * @var UiComponentFactory
     */
    protected $factory;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;


    /**
     * @param UiComponentFactory $factory
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        UiComponentFactory $factory,
        RequestInterface $request,
        FilterBuilder $filterBuilder
    ) {
        parent::__construct($factory, $request, $filterBuilder);
    }

    /**
     * Adds filters to collection using DataProvider filter results
     *
     * @param AbstractDb $collection
     * @return AbstractDb
     * @throws LocalizedException
     */
    public function getCollection(AbstractDb $collection)
    {
    	$customerExportEmailToggle = false;
    	if ($this->request->getParam(static::CUSTOMEREXPORT_TOGGLE)) {
    		$customerExportEmailToggle = true;
    	}

        $selected = $this->request->getParam(static::SELECTED_PARAM);
        $excluded = $this->request->getParam(static::EXCLUDED_PARAM);

        $isExcludedIdsValid = (is_array($excluded) && !empty($excluded));
        $isSelectedIdsValid = (is_array($selected) && !empty($selected));
        
        if ('false' !== $excluded && !$customerExportEmailToggle) {
            //@codeCoverageIgnoreStart
            if (!$isExcludedIdsValid && !$isSelectedIdsValid) {
                throw new LocalizedException(__('An item needs to be selected. Select and try again.'));
            }
            //@codeCoverageIgnoreEnd
        }

        $filterIds = $this->getFilterIds();
        if (\is_array($selected)) {
            $filterIds = array_unique(array_merge($filterIds, $selected));
        }
        $collection->addFieldToFilter(
            $collection->getResource()->getIdFieldName(),
            ['in' => $filterIds]
        );

        return $collection;
    }

    /**
     * Get data provider
     *
     * @return DataProviderInterface
     */
    private function getDataProvider()
    {
        if (!$this->dataProvider) {
            $component = $this->getComponent();
            $this->prepareComponent($component);
            $this->dataProvider = $component->getContext()->getDataProvider();
        }
        return $this->dataProvider;
    }

    /**
     * Get filter ids as array
     *
     * @return int[]
     */
    private function getFilterIds()
    {
        $idsArray = [];
        $this->applySelectionOnTargetProvider();
        if ($this->getDataProvider() instanceof \Magento\Ui\DataProvider\AbstractDataProvider) {
            // Use collection's getAllIds for optimization purposes.
            $idsArray = $this->getDataProvider()->getAllIds();
        } else {
            $dataProvider = $this->getDataProvider();
            $dataProvider->setLimit(0, false);
            $searchResult = $dataProvider->getSearchResult();
            // Use compatible search api getItems when searchResult is not a collection.
            foreach ($searchResult->getItems() as $item) {
                /** @var $item \Magento\Framework\Api\Search\DocumentInterface */
                $idsArray[] = $item->getId();
            }
        }
        return  $idsArray;
    }
    
}
