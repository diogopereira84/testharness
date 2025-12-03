<?php
declare(strict_types=1);

namespace Fedex\Catalog\Plugin\Block\Product\ProductList;

use Magento\Theme\Block\Html\Pager;
use Fedex\CatalogMvp\ViewModel\MvpHelper;


class CatalogToolbarPagination
{
    public function __construct(
        private readonly MvpHelper $catalogMvpHelper
    ) {}

    /**
     * @param Pager $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetFrameEnd(Pager $subject, mixed $result): mixed
    {
        return $this->catalogMvpHelper->shouldApplyCustomPagination()
            ? $this->calculateTotalPages($subject, $result)
            : $result;
    }

    /**
     * @param Pager $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetLastPageNum(Pager $subject, mixed $result): mixed
    {
        return $this->catalogMvpHelper->shouldApplyCustomPagination()
            ? $this->calculateTotalPages($subject, $result)
            : $result;
    }

    /**
     * @param Pager $pager
     * @param mixed $originalResult
     * @return mixed
     */
    private function calculateTotalPages(Pager $pager, mixed $originalResult): mixed
    {
        $collection = $pager->getCollection();
        $limit = $this->catalogMvpHelper->getSessionPageSize();

        if ($limit) {
            $collection->setPageSize((int) $limit);
        }

        $pageSize = $collection->getPageSize();
        $totalNum = $collection->getSize();

        return ($pageSize > 0) ? (int) ceil($totalNum / $pageSize) : $originalResult;
    }
}
