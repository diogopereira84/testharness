<?php

declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Api;

interface MenuTreeManagementInterface
{
    /**
     * Get customer company info
     *
     * @return array
     */
    public function getCustomerCompanyInfo(): array;

    /**
     * @param $outermostClass
     * @param $childrenWrapClass
     * @param $limit
     * @param $sharedCatalogCategoryId
     * @param $printProductCategoryId
     * @param $isAdminUser
     * @param $denyCategoryIds
     * @param $categoriesName
     * @return string
     */
    public function renderMegaMenuHtmlOptimized(
        $outermostClass,
        $childrenWrapClass,
        $limit,
        $sharedCatalogCategoryId,
        $printProductCategoryId,
        $isAdminUser,
        $denyCategoryIds,
        $categoriesName
    ): string;

    /**
     * @param $parentId
     * @param $node
     * @param $parentLevel
     * @param array $categoriesByParent
     * @param $isAdminUser
     * @param $companyType
     * @param $isPrintProduct
     * @param $customProductCategoryId
     * @param $skuOnlyProductCategoryId
     * @param $isNonStandardCatalog
     * @return string
     */
    public function renderCategoryTreeHtml(
        $parentId,
        $node,
        $parentLevel,
        array $categoriesByParent,
        $isAdminUser,
        $companyType,
        $isPrintProduct,
        $customProductCategoryId,
        $skuOnlyProductCategoryId,
        $isNonStandardCatalog
    ): string;
}
