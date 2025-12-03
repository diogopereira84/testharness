<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\FilterHandler\CategoryFilterHandler;

class CategoryFilterHandlerPlugin
{
    private const CATEGORY_IDS_ATTRIBUTE_NAME = 'categoryIds';

    /**
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        private CollectionFactory $categoryCollectionFactory
    ) {
    }

    /**
     * @param CategoryFilterHandler $subject
     * @param $result
     */
    public function afterGetFilterVariables(CategoryFilterHandler $subject, $result): array
    {
        if (!empty($result[0]['in'])) {
            $categoryCollection = $this->categoryCollectionFactory->create();
            $categoryCollection->addAttributeToSelect('*')
                ->addAttributeToFilter('url_path', ['in' => $result[0]['in']]);

            $result[0]['in'] = $categoryCollection->getAllIds();
            $result[0]['attribute'] = self::CATEGORY_IDS_ATTRIBUTE_NAME;
        }

        return $result;
    }
}
