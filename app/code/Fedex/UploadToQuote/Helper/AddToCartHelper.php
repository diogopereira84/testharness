<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Cart\CustomerCartResolver;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session as CheckoutSesion;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\B2b\Model\NegotiableQuoteManagement;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Model\Config;

/**
 * Upload to Quote AddToCartHelper class
 */
class AddToCartHelper extends AbstractHelper
{
    /**
     * @var Product $product
     */
    protected $product;

    /**
     * AddToCartHelper Constructor
     *
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param SerializerInterface $serializer
     * @param CustomerCartResolver $customerCartResolver
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Quote $quote
     * @param CheckoutSesion $checkoutSesion
     * @param NegotiableQuoteFactory $negotiableQuoteFactory
     * @param AdminConfigHelper $adminConfigHelper
     * @param NegotiableQuoteManagement $negotiableQuoteManagement
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     * @param Config $uploadToQuoteConfig
     */
    public function __construct(
        Context $context,
        protected CustomerSession $customerSession,
        protected CartRepositoryInterface $cartRepositoryInterface,
        protected SerializerInterface $serializer,
        protected CustomerCartResolver $customerCartResolver,
        protected ProductCollectionFactory $productCollectionFactory,
        protected Quote $quote,
        protected CheckoutSesion $checkoutSesion,
        protected NegotiableQuoteFactory $negotiableQuoteFactory,
        protected AdminConfigHelper $adminConfigHelper,
        protected NegotiableQuoteManagement $negotiableQuoteManagement,
        private GraphqlApiHelper $graphqlApiHelper,
        protected LoggerInterface $logger,
        protected ToggleConfig $toggleConfig,
        private Config $uploadToQuoteConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Add quote items to cart
     *
     * @param int $storeId
     * @param int $quoteId
     * @return boolean
     */
    public function addQuoteItemsToCart($storeId, $quoteId)
    {
        $negotiableQuote = $this->negotiableQuoteFactory->create()->load($quoteId);
        $oldStatus = $negotiableQuote->getStatus();
        $isCartActivated = false;
        if ($oldStatus != NegotiableQuoteInterface::STATUS_ORDERED) {
            $isCartActivated = true;
            if (!$this->utoqApprovaFixToggle()) {
                $negotiableQuote->setIsRegularQuote(0);
                $negotiableQuote->save();
                $this->logger->info(__METHOD__ . ':' . __LINE__
                    .': Quote id '.$quoteId.' set in checkout session');
            }
            $this->negotiableQuoteManagement->updateNegotiableSnapShot($quoteId);
            $this->logger->info(__METHOD__ . ':' . __LINE__
                .': Snapshot is updated against Quote id '.$quoteId.'');
            $currentQuoteId = $this->checkoutSesion->getQuote()->getId() ?? '';
            $requestedQuote = $this->cartRepositoryInterface->get($quoteId);
            $requestedQuote->setIsActive(true);
            $this->cartRepositoryInterface->save($requestedQuote);
            $this->logger->info(__METHOD__ . ':' . __LINE__
                .': Make cart active against quote id '.$quoteId.'');
            if ($this->utoqApprovaFixToggle()) {
                $negotiableQuote->setIsRegularQuote(0);
                $negotiableQuote->save();
                $this->logger->info(__METHOD__ . ':' . __LINE__
                    .': Quote id '.$quoteId.' set in checkout session');
            }
            if ($currentQuoteId && $currentQuoteId != $quoteId) {
                $currentQuote = $this->cartRepositoryInterface->get($currentQuoteId);
                $currentQuote->setIsActive(false);
                $this->cartRepositoryInterface->save($currentQuote);
                $currentNegotiatedQuote =  $this->negotiableQuoteFactory->create()->load($currentQuoteId);
                if (!$currentNegotiatedQuote->getId() && !$requestedQuote->getIsBid()) {
                    $quote = $this->quote->load($quoteId);
                    $quoteItems = $quote->getAllVisibleItems();
                    $cart = $this->uploadToQuoteConfig->isTk4674396ToggleEnabled()
                        ? $quote
                        : $this->customerCartResolver->resolve($this->customerSession->getCustomerId());
                    if ($currentQuoteId == $cart->getId()) {
                        $quoteProductIds = $this->getProductIdsByQuote($quoteItems);
                        $products = $this->getQuoteProducts($storeId, $quoteProductIds);
                        foreach ($quoteItems as $item) {
                            $product = $products[$item->getProductId()];
                            $this->addItemToCart($cart, clone $product, $item);
                        }
                        $this->cartRepositoryInterface->save($cart);
                    }

                    $this->logger->info(__METHOD__ . ':' . __LINE__
                        .': Current quote items are merged to negotiable quote against '.$quoteId.'');
                }
            }
            if ($this->toggleConfig->getToggleConfigValue('xmen_team_d191746_approval_decline_change_request_fix')) {
                $this->checkoutSesion->setQuoteId($quoteId);
            }
            $this->checkoutSesion->replaceQuote($requestedQuote);
            $this->logger->info(__METHOD__ . ':' . __LINE__
                .': Quote against '.$quoteId.' is replaced with current quote.');
            $this->graphqlApiHelper->setQuoteNotes("Customer has approved quote ".$quoteId, $quoteId, "quote_approved");
        }

        return $isCartActivated;
    }

    /**
     * Add Item to cart
     *
     * @param Object $cart
     * @param Object $product
     * @param Object $item
     * @return void
     */
    public function addItemToCart(Quote $cart, Product $product, $item)
    {
        $info = json_decode($item->getOptionByCode('info_buyRequest')->getValue(), true);
        $info = new \Magento\Framework\DataObject($info);
        $info->setData('qty', $item->getQty());
        $randomNumber = rand(1, 100000000000000);
        $customOptions = [
            'label' => 'fxoProductInstance',
            'value' => $item->getProductId() . $randomNumber,
        ];
        $product->addCustomOption('custom_option', $this->serializer->serialize($customOptions));

        if ($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            $this->setBundleData($product, $item);
        }

        $cart->addProduct($product, $info);
    }

    /**
     * Get products by store id and product ids
     *
     * @param int $storeId
     * @param array $orderItemProductIds
     * @return Object
     */
    private function getQuoteProducts(string $storeId, array $orderItemProductIds): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }

    /**
     * Get products ids by quote
     *
     * @param object $quoteItems
     * @return array
     */
    public function getProductIdsByQuote($quoteItems)
    {
        $quoteProductIds = [];
        foreach ($quoteItems as $item) {
            $quoteProductIds[] = $item->getProductId();
        }

        return $quoteProductIds;
    }

    /**
     * Deactivate quote on exception
     *
     * @param int $quoteId
     * @return void
     */
    public function deactivateQuote($quoteId)
    {
        $this->adminConfigHelper->deactivateQuote($quoteId);
    }

    /**
     * check approval fix toggle are enabled or not
     *
     * @return boolean
     */
    public function utoqApprovaFixToggle()
    {
        return $this->adminConfigHelper->utoqApprovaFixToggle();
    }

    /**
     * Set bundle data for the product
     *
     * @param Product $product
     * @param $item
     * @return void
     * @throws LocalizedException
     */
    private function setBundleData(Product $product, $item): void
    {
        $childInfo = [];
        foreach ($item->getChildren() as $childItem) {
            $childInfo[] = json_decode($childItem->getOptionByCode('info_buyRequest')->getValue(), true);
        }
        $product->addCustomOption('productsData', $this->serializer->serialize($childInfo));
        $product->addCustomOption('bundle_instance_id_hash', $this->generateInstanceIdHash($item->getChildren()));
    }

    /**
     * Generate a unique hash for the bundle instance ID based on child items
     *
     * @param $children
     * @return string
     */
    private function generateInstanceIdHash($children): string
    {
        $instanceIdHash = [];

        /** @var Quote\Item $child */
        foreach ($children as $child) {
            $info = json_decode($child->getOptionByCode('info_buyRequest')->getValue(), true);
            $instanceIdHash[] = $info['external_prod'][0]['instanceId'] ?? '';
        }

        return implode('_', $instanceIdHash);
    }
}
