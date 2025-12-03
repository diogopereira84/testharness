<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogMvp\Controller\Index;

use Exception;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Helper\Image;
use Fedex\CatalogMvp\Model\Duplicate;
use Magento\Customer\Model\SessionFactory;
use Fedex\CatalogMvp\Api\WebhookInterface;
use Fedex\CatalogMvp\Helper\CatalogDocumentRefranceApi;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductRepository;

/**
 * Class Duplicate Product
 * Handle the duplicate product from kebab of the CatalogMvp
 */
class DuplicateProduct  implements ActionInterface
{
    public const DUPLICATED_MESSAGE = 'Item has been duplicated. The item will be available shortly.';
    /**
     * DuplicateProduct Constructor
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CatalogMvp $helper
     * @param Image $imageHelper
     * @param Product $product
     * @param Duplicate $duplicate
     * @param SessionFactory $sessionFactory
     * @param WebhookInterface $webhookInterface
     * @param CatalogDocumentRefranceApi $catalogdocumentrefapi
     */
    public function __construct(
        protected Context $context,
        protected JsonFactory $jsonFactory,
        protected CatalogMvp $helper,
        private Image $imageHelper,
        private Product $product,
        private Duplicate $duplicate,
        protected SessionFactory $sessionFactory,
        protected WebhookInterface $webhookInterface,
        protected CatalogDocumentRefranceApi $catalogdocumentrefapi,
        /** @var ToggleConfig */
        protected ToggleConfig $toggleConfig,
        protected LoggerInterface $logger,
        protected ProductRepository $productRepository
    )
    {
    }

    /**
    * Function to execute duplicate product
    */
    public function execute()
    {
        $isToggleEnable = $this->helper->isMvpSharedCatalogEnable();
        $isAdminUser = $this->helper->isSharedCatalogPermissionEnabled();
        $customerSessionId = null;

        if ($isToggleEnable && $isAdminUser) {
            try {
                $postData = $this->context->getRequest()->getParams();

                    $customerSessionId = $this->helper->getCustomerSessionId();
                    $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId . ' Catalog Mvp Duplicate Product Data ' . json_encode($postData));

                $productId = $postData['pid'];
                $viewmode = $postData['viewMode'];
                $customerSession = $this->sessionFactory->create();
                $customerGroupId = $customerSession->getCustomer()->getGroupId();
                $json = $this->jsonFactory->create();
                // load product from ID
                $product =$this->productRepository->getById($productId);
                // duplicate product
                $newProduct = $this->duplicate->copy($product);

                    $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId . ' Catalog Mvp Duplicate Product after successfully copy');

                // Set quantity to the product
                $newProduct->setStockData(
                    [
                        'use_config_manage_stock' => 0,
                        'is_in_stock' => 1,
                        'qty' => 9999,
                        'manage_stock' => 0,
                        'use_config_notify_stock_qty' => 0,
                    ]
                );
                $newProduct->setData('tier_price', []);
                $newProduct->getResource()->save($newProduct);
                // saving again with product different visibility as for the default store or any store default is not visibile during duplicate
                $reSaveNewProduct = $this->productRepository->getById($newProduct->getId());
                $reSaveNewProduct->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH); // setting catalog, search with on-demand storeId which is preset with $newProduct object.
                $this->productRepository->save($reSaveNewProduct);

                // code added to create refernce
                $documentIds = $this->catalogdocumentrefapi->getDocumentId($newProduct->getExternalProd());
                foreach ($documentIds as $documentId) {
                    $this->catalogdocumentrefapi->addRefernce($documentId, $newProduct->getId());
                }
                $this->catalogdocumentrefapi->updateProductDocumentEndDate($product, 'customer_admin');

                    $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId . ' Catalog Mvp Duplicate Product update document expiry');

                $requestData[] = [
                    "sku" => $newProduct->getSku(),
                    "customer_group_id" => $customerGroupId,
                    "shared_catalog_id" => null
                ];
                $this->webhookInterface->addProductToRM($requestData);

                $this->helper->insertProductActivity($newProduct->getId(), "UPDATE");

                //get the image URL of product
                $imageUrl = $this->imageHelper->init($newProduct, 'product_page_image_large')->getUrl();

                    $this->logger->info(__METHOD__.':'.__LINE__.':'.__FILE__. $customerSessionId . ' Catalog Mvp Duplicate Product image set after');

                $price = $newProduct->getPrice();
                if(!$price) {
                    $price = 0.00;
                }
                $newPrice = number_format($price, 2, '.', '');
                $newdate = date('m/d/Y', strtotime($newProduct->getUpdatedAt()));
                if ($newProduct->getPublished()) {
                    $published = '<input type="checkbox" id="' . $newProduct->getId() . '" checked >';
                } else {
                    $published = '<input type="checkbox" id="' . $newProduct->getId() . '">';
                }

                if ($viewmode == 'grid') {
                    $view = '<li class="duplicate item product product-item category-product" id="' . $newProduct->getId() . '">
                            <div class="product-item-info" id="product-item-info_'
                            . $newProduct->getId() . '" data-container="product-grid">
                            <input class="product-item-checkbox list-checkbox" type="checkbox" id="product-item-checkbox_'
                            . $newProduct->getId() . '" name="' . $newProduct->getId() . '" value="product-name'
                            . $newProduct->getId() . '" data-item-sku="' . $newProduct->getSku() . '">
                            <div class="kebab">
                                <figure></figure>
                                <figure class="middle"></figure>
                                <p class="cross">x</p>
                                <figure></figure>
                            </div>
                            <div class="kebab-options" style="display: none;">
                                <div class="dropdown-menu-list">
                                    <div class="menu-item">
                                        <div class="menu-item-label">
                                            <a href="#">Add to Cart</a>
                                        </div>
                                        <div class="menu-item-label">
                                            <a href="#">Edit</a>
                                        </div>
                                        <div class="menu-item-label Delete">
                                            <a href="#">Delete</a>
                                        </div>
                                        <div class="menu-item-label Duplicate">
                                            <a href="javascript:void(0);" data-item-id="' . $newProduct->getId() . '">Duplicate
                                            </a>
                                        </div>
                                        <div class="menu-item-label Move">
                                            <a href="#">Move</a>
                                        </div>
                                        <div class="menu-item-label action_kebab_item_rename">
                                            <a href="javascript:void(0);" data-item-id="' . $newProduct->getId() . '">Rename
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <span class="product-image-container product-image-container-3038" style="width: 84px;">
                            <span class="product-image-wrapper" style="padding-bottom: 125%;">
                            <img class="product-image-photo" src="'
                            .$imageUrl.'" loading="lazy" width="84" height="105" alt="5 Aug P2"></span>
                            </span>
                            <style>.product-image-container-' . $newProduct->getId() . ' {
                            width: 84px;
                            }
                            .product-image-container-' . $newProduct->getId() . ' span.product-image-wrapper {
                            padding-bottom: 125%;
                            }</style>
                            <script type="text/javascript">prodImageContainers =
                            document.querySelectorAll(".product-image-container-'. $newProduct->getId() . '");
                            for (var i = 0; i < prodImageContainers.length; i++) {
                            prodImageContainers[i].style.width = "84px";
                            }
                            prodImageContainersWrappers = document.querySelectorAll(
                            ".product-image-container-' . $newProduct->getId() . '  span.product-image-wrapper"
                            );
                            for (var i = 0; i < prodImageContainersWrappers.length; i++) {
                            prodImageContainersWrappers[i].style.paddingBottom = "125%";
                            }</script>
                            <div class="product-name-list">
                            <strong class="product name product-item-name dbclick-item-rename" id="product-item-'
                            . $newProduct->getId() . '" data-item-id="' . $newProduct->getId() . '
                            " contenteditable="true" data-rename-type="item" data-rename-id="'
                            . $newProduct->getId() . '">
                            <input class="rename-editable" data-rename-id="' . $newProduct->getId() . '" id="product-item-'
                            . $newProduct->getId() . '" value="' . $newProduct->getName() . '(2)" maxlength="50" contenteditable="true" data-rename-type="item">
                            </strong>
                                </div>
                                <div class="modified-info">
                                <span>'.$newdate.'</span>
                                <br>
                                </div>
                                <div class="product details product-item-details">
                                <!-- get product price -->
                                <div class="price-box price-final_price" data-role="priceBox" data-product-id="'
                                . $newProduct->getId() . '" data-price-box="product-id-'
                                . $newProduct->getId() . '">
                            <span class="price-container price-final_price tax weee">
                                <span id="product-price-' . $newProduct->getId() . '"  data-price-type="finalPrice"
                                class="price-wrapper "><span class="price">$'.$newPrice.'</span></span>
                                </span>
                            <span class="offer-wrapper" style="display: none;">
                            <div class="offer-min-shipping-price offer-shipping best-offer-shipping" data-shipping-offer-id=""
                            style="display: none">
                            + <span class="offer-min-shipping-price-amount-excl-tax">
                            $0.00</span>&nbsp;shipping</div>
                            </span>
                            <div class="offer-price-description" style="display: none;">
                            </div>
                            </div><div class="product-item-inner">
                            <div class="product actions product-item-actions">
                            <div data-role="add-to-links" class="actions-secondary">
                            </div>
                            </div>
                            </div>
                            </div>
                            </div>
                            </li>';
                } else {
                        $view = '<li class="duplicate list item product product-item category-product" id="' . $newProduct->getId() . '">
                        <div class="product-item-info" id="product-item-info_'
                        . $newProduct->getId() . '" data-container="product-list">
                            <div class="drag-drop catalog-item-move ui-draggable ui-draggable-handle" id="'
                            . $newProduct->getSku() . '">
                                    <div class="drag-drop-img"></div>
                                </div>
                                <input class="product-item-checkbox list-checkbox" type="checkbox" id="product-item-checkbox_'
                                . $newProduct->getId() . '" name="' . $newProduct->getId() . '" value="product-name'
                                . $newProduct->getId() . '" data-item-sku="' . $newProduct->getSku() . '">
                                <span class="product-image-container product-image-container-' . $newProduct->getId()
                                . '" style="width: 240px;">
                    <span class="product-image-wrapper" style="padding-bottom: 125%;">
                    <img class="product-image-photo" src="'.$imageUrl.'" loading="lazy" width="240"
                    height="300" alt="5 Aug P2"></span>
                    </span>
                    <style>.product-image-container-' . $newProduct->getId() . ' {
                    width: 240px;
                    }
                    .product-image-container-' . $newProduct->getId() . ' span.product-image-wrapper {
                    padding-bottom: 125%;
                    }</style>
                    <script type="text/javascript">prodImageContainers = document.querySelectorAll(".product-image-container-'
                    . $newProduct->getId() . '");
                    for (var i = 0; i < prodImageContainers.length; i++) {
                    prodImageContainers[i].style.width = "240px";
                    }
                    prodImageContainersWrappers = document.querySelectorAll(
                    ".product-image-container-' . $newProduct->getId() . '  span.product-image-wrapper"
                    );
                    for (var i = 0; i < prodImageContainersWrappers.length; i++) {
                    prodImageContainersWrappers[i].style.paddingBottom = "125%";
                    }</script>
                            <div class="product-name-list">
                            <strong class="product name product-item-name dbclick-item-rename" id="product-item-'
                            . $newProduct->getId() . '" data-item-id="' . $newProduct->getId() . '
                            " contenteditable="true" data-rename-type="item" data-rename-id="'
                            . $newProduct->getId() . '">
                            <input class="rename-editable" data-rename-id="' . $newProduct->getId() . '" id="product-item-'
                            . $newProduct->getId() . '" value="' . $newProduct->getName() . '(2)" maxlength="50" contenteditable="true" data-rename-type="item">
                            </strong>
                            </div>
                            <div class="modified-info">
                                <span>'.$newdate.'</span>
                                <br>
                            </div>
                            <div class="product details product-item-details">
                                <!-- get product price -->
                                <div class="price-box price-final_price" data-role="priceBox" data-product-id="'
                                . $newProduct->getId() . '" data-price-box="product-id-' . $newProduct->getId() . '">
                    <span class="price-container price-final_price tax weee">
                    <span id="product-price-' . $newProduct->getId() . '"  data-price-type="finalPrice" class="price-wrapper ">
                    <span class="price">$'.$newPrice.'</span></span>
                    </span>
                    <span class="offer-wrapper" style="display: none;">
                    <div class="offer-min-shipping-price offer-shipping best-offer-shipping" data-shipping-offer-id=""
                    style="display: none">
                        + <span class="offer-min-shipping-price-amount-excl-tax">
                        $0.00</span>&nbsp;shipping</div>
                    </span>
                    </div><div class="product-item-inner"><div class="product actions product-item-actions">
                    <div data-role="add-to-links" class="actions-secondary"></div></div></div></div>
                    <div class="published">
                    <label class="switch">'.$published.'
                            <span class="custom-slider round"></span>
                            <span class="labels" data-on="ON" data-off="OFF"></span>
                    </label>
                    </div>
                    <div class="kebab">
                        <div class="kebab-image product-list"></div>
                    </div>
                            <div class="kebab-options">
                                <div class="dropdown-menu-list">
                                    <div class="menu-item">
                                        <div class="menu-item-label">
                                            <a href="#">Add to Cart</a>
                                        </div>
                                        <div class="menu-item-label">
                                            <a
                                            href="https://staging3.office.fedex.com/ondemand/catalogmvp/configurator/index/sku/'
                                            . $newProduct->getSku() . '">Edit</a>
                                        </div>
                                        <div class="menu-item-label Delete">
                                            <a href="#">Delete</a>
                                        </div>
                                        <div class="menu-item-label Duplicate">
                                            <a href="javascript:void(0);" data-item-id="'
                                            . $newProduct->getId() . '">Duplicate</a>
                                        </div>
                                        <div class="menu-item-label Move">
                                            <a href="#">Move</a>
                                        </div>
                                        <div class="menu-item-label action_kebab_item_rename">
                                            <a href="javascript:void(0);" data-item-id="'
                                            . $newProduct->getId() . '">
                                                Rename</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>';
                }
                $data = [];
                $data['html'] = $view;
                //B-2034092 - Catalog Messaging Updates
                $data['duplicate'] = 1;
                $data['message'] = __(self::DUPLICATED_MESSAGE);
                $json->setData($data);
                return $json;
            } catch (\Exception $error) {
                $customerSessionId = $this->helper->getCustomerSessionId();
                $this->logger->critical(__METHOD__ . ':' . __LINE__ . $customerSessionId .
                    ' Catalog Mvp Error in duplicate product : ' . $error->getMessage());
            }
        }
    }
}
