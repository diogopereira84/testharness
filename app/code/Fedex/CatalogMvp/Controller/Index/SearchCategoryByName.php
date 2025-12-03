<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Controller\Index;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\App\RequestInterface;

/**
 * Controller for obtaining stores suggestions by query.
 */
class SearchCategoryByName implements ActionInterface
{
    /**
     * constructor function
     *
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param RequestInterface $request
     * @return void
     */
    public function __construct(
        Context $context,
        protected CategoryFactory $categoryFactory,
        protected ResultFactory $resultFactory,
        protected CategoryRepository $categoryRepository,
        protected JsonFactory $resultJsonFactory,
        private ToggleConfig $toggleConfig,
        private CatalogMvp $catalogMvpHelper,
        protected RequestInterface $request
    )
    {
    }
    /**
     * Get Store/Store view list
     *
     * @return Json
     */
    public function execute()
    {
        $html = '';
        $categoryname = $this->request->getParam('search_category_by_name');
        $currentcategoryId = $this->request->getParam('currentCategoryId');
        if ($categoryname != '') {

                $category=$this->categoryRepository->get($currentcategoryId);

            $categoryIds= $category->getAllChildren(false);
            $collection = $this->categoryFactory->create()->getCollection();
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('entity_id', ['in'=>$categoryIds]);
            $collection->addAttributeToFilter('name', ['like' => '%'.$categoryname.'%'])->setPageSize(10);
            if (!empty($collection)) {
                foreach ($collection as $key => $categoryData) {
                    $childofprintproduct_level0 = $categoryData->getName();
                    $html .= '<li class="mvp-catalog-move-popup-category-l-0">
                            <div class = "sub-category-container-level-0 level-0 sub-cat-div" id="move-'.$categoryData->getId().'">
                                <div class = "toggle-icon-level-0" id="toggle-'.$categoryData->getId().'">
                                    <div class="disclosere-icon-closed level-0 display"
                                        style="visibility: hidden;"></div>
                                    <div class="disclosere-icon-open level-0" style="display: none;"></div>
                                </div>
                                <div class="folder-img-icon"></div>
                                <span class = "category-name-level-0 sub-cat-div-name">'
                                .$childofprintproduct_level0. '</span>
                            </div>
                        </li>';
                }
            }
            else {
                $html .= '<li class="mvp-catalog-move-popup-category-l-0">
                           <span class = "no-collection-msg">No Data found</span>
                        </li>';
            }
        } else {
            $novalsearched = $this->noValueSearched($currentcategoryId,$html);
            $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            return $rawResult->setContents($novalsearched);
        }
       /** @var Raw $rawResult */
       $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);
       return $rawResult->setContents($html);
    }


    public function noValueSearched($currentcategoryId,$html1)
    {

        $categoryrepo = $this->categoryRepository->get($currentcategoryId);
        $subcategorys = $categoryrepo->getChildrenCategories();
        foreach ($subcategorys as $childSubcategory) {
            $childofprintproduct_level0 = $childSubcategory->getName();
            $html1 .= '<li class="mvp-catalog-move-popup-category-l-0">
            <div class = "sub-category-container-level-0 level-0 sub-cat-div" id="move-'.$childSubcategory->getId().'">';
                    if ($childSubcategory->hasChildren()) {
                    $html1 .= '<div class = "toggle-icon-level-0" id="toggle-'.$childSubcategory->getId().'">
                        <div class="disclosere-icon-closed level-0 display"></div>
                        <div class="disclosere-icon-open level-0" style="display: none;"></div>
                    </div>';
                }
                    else {
                    $html1 .= '<div class = "toggle-icon-level-0" id="toggle-'.$childSubcategory->getId().'">
                        <div class="disclosere-icon-closed level-0 display"
                            style="visibility: hidden;"></div>
                        <div class="disclosere-icon-open level-0" style="display: none;"></div>
                        </div>';
                }
                $html1 .= '<div class="folder-img-icon"></div>
                    <span class = "category-name-level-0 sub-cat-div-name">'
                        .$childofprintproduct_level0. '</span>
                </div>';
                if ($childSubcategory->hasChildren()) {
                    $childCategoryObj = $this->categoryRepository->get($childSubcategory->getId());
                    $childSubcategories = $childCategoryObj->getChildrenCategories();
                    foreach ($childSubcategories as $childSubcategory_level1) {
                            $childofprintproduct_level1 = $childSubcategory_level1->getName();
                $html1 .= '<ul class = "category-tree-level-1">
                    <li class="mvp-catalog-move-popup-category-l-1" style="display: none;">
                        <div class = "sub-category-container-level-1 sub-cat-div" id="move-'.$childSubcategory_level1->getId().'">';
                            if ($childSubcategory_level1->hasChildren()) {
                                $html1 .= '<div class = "toggle-icon-level-1" id="toggle-'.$childSubcategory_level1->getId().'">
                                <div class="disclosere-icon-closed level-1 display"></div>
                                <div class="disclosere-icon-open level-1" style="display: none;"></div>
                            </div>';
                        }
                        else {
                            $html1 .= '<div class = "toggle-icon-level-1" id="toggle-'.$childSubcategory_level1->getId().'">
                            <div class="disclosere-icon-closed level-1 display"
                                style="visibility: hidden;"></div>
                            <div class="disclosere-icon-open level-1" style="display: none;"></div>
                        </div>';
                        }
                        $html1 .= '<div class="folder-img-icon"></div>
                            <span class = "category-name-level-1 sub-cat-div-name">'
                                .$childofprintproduct_level1. '</span>
                        </div>';
                        $isCatalogBreakPointEnabled = $this->catalogMvpHelper->getCatalogBreakpointToggle();
                        if($isCatalogBreakPointEnabled) {
                            $html1 = $this->getCategoryLevelAll($childSubcategory_level1,$html1);
                        } else {
                            $html1 = $this->getCategoryLeveltwo($childSubcategory_level1,$html1);
                        }
                    $html1 .='</li>
                </ul>';
                }
            }
            $html1 .=  '</li>';
        }
        $html1 .=   '</ul>';
        return $html1;
    }

    public function getCategoryLeveltwo ($childSubcategory_level1,$html2){

        if ($childSubcategory_level1->hasChildren()) {
            $childCategoryObj =
            $this->categoryRepository->get($childSubcategory_level1->getId());
        $childSubcategoriess = $childCategoryObj->getChildrenCategories();
        foreach ($childSubcategoriess as $childSubcategory_level2) {
                $childofprintproduct_level2 = $childSubcategory_level2->getName();
        $html2 .='<ul class = "category-tree-level-2">
            <li class="mvp-catalog-move-popup-category-l-2" style="display: none;">
                <div class = "sub-category-container-level-2 sub-cat-div" id="move-'.$childSubcategory_level2->getId().'">
                    <div class="disclosere-icon-closed level-2"
                        style="visibility: hidden;" ></div>
                    <div class="disclosere-icon-open level-2"
                    style="display: none;" ></div>
                    <div class="folder-img-icon"></div>
                    <span class = "category-name-level-2 sub-cat-div-name">'
                        . $childofprintproduct_level2.'</span>
                </div>
            </li>
        </ul>';
            }
        }
        return $html2;
    }

    //@codeCoverageIgnoreStart
    public function getCategoryLevelAll($childSubcategory,$html1){
        if ($childSubcategory->hasChildren()) {
            $childCategoryObj = $this->categoryRepository->get($childSubcategory->getId());
            $childSubcategories = $childCategoryObj->getChildrenCategories();
            foreach ($childSubcategories as $childSubcategory_level1) {
                    $childofprintproduct_level1 = $childSubcategory_level1->getName();
                    $level2 = $childSubcategory_level1->getLevel() - 3;
        $html1 .= '<ul class = "all-level-category-tree category-tree-level-'.$level2.'">
            <li class="mvp-catalog-move-popup-category-l-'.$level2.' mvp-move-popup-cat-levels" style="display: none;">
                <div class = "sub-category-container-level-all sub-category-container-level-'.$level2.' sub-cat-div" id="move-'.$childSubcategory_level1->getId().'">';
                    if ($childSubcategory_level1->hasChildren()) {
                        $html1 .= '<div class = "toggle-icon-level-all toggle-icon-level-'.$level2.'" id="toggle-'.$childSubcategory_level1->getId().'">
                        <div class="disclosere-icon-closed level-all level-'.$level2.' display"></div>
                        <div class="disclosere-icon-open level-all level-'.$level2.'" style="display: none;"></div>
                        </div>';
                    } else {
                    $html1 .= '<div class = "toggle-icon-level-all toggle-icon-level-'.$level2.'" id="toggle-'.$childSubcategory_level1->getId().'">
                                <div class="disclosere-icon-closed level-all level-'.$level2.' display"
                                    style="visibility: hidden;"></div>
                                <div class="disclosere-icon-open level-all level-'.$level2.'" style="display: none;"></div>
                            </div>';
                    }
                    $html1 .= '<div class="folder-img-icon"></div>
                        <span class = "category-name-level-'.$level2.' category-name-level-all sub-cat-div-name">'
                            .$childofprintproduct_level1. '</span>
                    </div>';
                    $html1 = $this->getCategoryLevelAll($childSubcategory_level1,$html1);
                    $html1 .='</li>
                    </ul>';
            }
        }

        return $html1;
    }
    //@codeCoverageIgnoreEnd
}
