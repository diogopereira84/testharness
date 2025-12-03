<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Registry;
use Magento\Framework\App\Request\Http;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Catalog\ViewModel\ProductList;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use Magento\Catalog\Helper\Output;
use Fedex\CustomerCanvas\ViewModel\CanvasParams;


/**
 * Controller for obtaining stores suggestions by query.
 */
class ProductListAjax extends Action
{
    /**
     * constructor function
     *
     * @param Context $context
     * @return void
     */
    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        private PageFactory $resultPageFactory,
        private JsonFactory $resultJsonFactory,
        private ProductList $viewmodel,
        private MvpHelper $mvpViewModel,
        private Registry $registry,
        private CategoryRepository $categoryRepository,
        private CatalogMvp $mvpHelper,
        private Http $request,
        private Output $outputHelper,
        private CanvasParams $dyesubModel
    ) {
        parent::__construct($context);
        $this->resultFactory = $resultFactory;
    }
    /**
     * Get Store/Store view list
     *
     * @return Json
     */
    public function execute()
    {
        $html = '';
        $resultPage = $this->resultPageFactory->create();
        $categoryId = $this->request->getParam('id');
        if($categoryId) {
            $category  = $this->categoryRepository->get($categoryId);
            $this->registry->register('current_category', $category);
        }

        $isCustomerAdmin = $this->mvpHelper->isSharedCatalogPermissionEnabled();
        if($this->mvpHelper->getMergedSharedCatalogFilesToggle()) {
            // @codeCoverageIgnoreStart
            $html = $resultPage->getLayout()
                    ->createBlock('Fedex\CatalogMvp\Block\Product\ListProduct',
                    'product_list_view_model',
                        [
                            'data' => [
                                'product_list_view_model' => $this->viewmodel,
                                'view_model_mvphelper' => $this->mvpViewModel,
                                'outputHelper' => $this->outputHelper,
                                'dyesubViewModel' => $this->dyesubModel
                            ]
                        ]
                    )
                    ->setTemplate('Magento_Catalog::product/product-category-list-customer-merged.phtml')
                    ->setChild('breadcrumbs', $resultPage->getLayout()->createBlock('Magento\Catalog\Block\Breadcrumbs'))
                    ->setChild('custom.modal.delete.popup', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template')->setTemplate('Magento_Catalog::product/delete-popup-modal.phtml'))
                    ->setChild('change.request.modal', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template')->setTemplate('Magento_Catalog::product/change-request-modal.phtml'))
                    ->setChild('content.renew.modal', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template')->setTemplate('Magento_Catalog::product/content-renew-modal.phtml'))
                    ->setChild('renew.success.modal', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template')->setTemplate('Magento_Catalog::product/renew-success-modal.phtml'))
                    ->setChild('catalog.list.select.number', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template',
                    'ajax.catalog.list.select.number',
                        [
                            'data' => [
                                'product_list_view_model' => $this->viewmodel,
                                'view_model_mvphelper' => $this->mvpViewModel,
                                'dyesubViewModel' => $this->dyesubModel
                            ]
                        ]
                    )->setTemplate('Magento_Catalog::product/select-number.phtml'))
                    ->setChild('catalog.list.right.panel', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template',
                    'ajax.catalog.list.right.panel',
                        [
                            'data' => [
                                'product_list_view_model' => $this->viewmodel,
                                'view_model_mvphelper' => $this->mvpViewModel,
                                'dyesubViewModel' => $this->dyesubModel
                            ]
                        ]
                    )->setTemplate('Magento_Catalog::product/right-panel.phtml'))
                    ->setChild('custom.modal.move.operation', $resultPage->getLayout()->createBlock('Magento\Framework\View\Element\Template',
                    'ajax.custom.modal.move.operation',
                        [
                            'data' => [
                                'product_list_view_model' => $this->viewmodel,
                                'dyesubViewModel' => $this->dyesubModel
                            ]
                        ]
                    )->setTemplate('Magento_Catalog::product/move-operation-modal.phtml'))
                    ->setChild('toolbar', $resultPage->getLayout()->createBlock('Magento\Catalog\Block\Product\ProductList\Toolbar',
                    'ajax_view_model_mvphelper',
                        [
                            'data' => [
                                'view_model_mvphelper' => $this->mvpViewModel,
                            ]
                        ]
                    )->setTemplate('Magento_Catalog::product/list/ajaxToolbar.phtml')->setCollection([])
                    ->setChild('product_list_toolbar_pager',$resultPage->getLayout()->createBlock('Magento\Theme\Block\Html\Pager')->setTemplate('Magento_Theme::html/ajax-shared-catalog-pager.phtml')))
                    ->toHtml();
                // @codeCoverageIgnoreEnd
        } else {
            if($isCustomerAdmin) {
                $html = $resultPage->getLayout()
                    ->createBlock('Fedex\CatalogMvp\Block\Product\ListProduct',
                    'product_list_view_model',
                        [
                            'data' => [
                                'product_list_view_model' => $this->viewmodel,
                                'view_model_mvphelper' => $this->mvpViewModel,
                                'outputHelper' => $this->outputHelper,
                                'dyesubViewModel' => $this->dyesubModel
                            ]
                        ]
                    )
                    ->setTemplate('Magento_Catalog::product/product-category-list-customer-admin.phtml')
                    ->setChild('breadcrumbs', $resultPage->getLayout()->createBlock('Magento\Catalog\Block\Breadcrumbs'))
                    ->setChild('toolbar', $resultPage->getLayout()->createBlock('Magento\Catalog\Block\Product\ProductList\Toolbar',
                    'ajax_view_model_mvphelper',
                        [
                            'data' => [
                                'view_model_mvphelper' => $this->mvpViewModel,
                            ]
                        ]
                    )->setTemplate('Magento_Catalog::product/list/ajaxToolbar.phtml')->setCollection([])
                    ->setChild('product_list_toolbar_pager',$resultPage->getLayout()->createBlock('Magento\Theme\Block\Html\Pager')->setTemplate('Magento_Theme::html/ajax-shared-catalog-pager.phtml')))
                    ->toHtml();
            } else {
                $html = $resultPage->getLayout()
                    ->createBlock('Fedex\CatalogMvp\Block\Product\ListProduct',
                    'product_list_view_model',
                        [
                            'data' => [
                                'product_list_view_model' => $this->viewmodel,
                                'view_model_mvphelper' => $this->mvpViewModel,
                                'outputHelper' => $this->outputHelper,
                                'dyesubViewModel' => $this->dyesubModel
                            ]
                        ]
                    )
                    ->setTemplate('Magento_Catalog::product/product-category-list-customer.phtml')
                    ->setChild('breadcrumbs', $resultPage->getLayout()->createBlock('Magento\Catalog\Block\Breadcrumbs'))
                    ->setChild('toolbar', $resultPage->getLayout()->createBlock('Magento\Catalog\Block\Product\ProductList\Toolbar',
                    'ajax_view_model_mvphelper',
                        [
                            'data' => [
                                'view_model_mvphelper' => $this->mvpViewModel,
                            ]
                        ]
                    )->setTemplate('Magento_Catalog::product/list/ajaxToolbar.phtml')->setCollection([])
                    ->setChild('product_list_toolbar_pager',$resultPage->getLayout()->createBlock('Magento\Theme\Block\Html\Pager')->setTemplate('Magento_Theme::html/ajax-shared-catalog-pager.phtml')))
                    ->toHtml();
            }
        }



       /** @var Raw $rawResult */
       $rawResult = $this->resultFactory->create(ResultFactory::TYPE_RAW);
       return $rawResult->setContents($html);
    }
}
