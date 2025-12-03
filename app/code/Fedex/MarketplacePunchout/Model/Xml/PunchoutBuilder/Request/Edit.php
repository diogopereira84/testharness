<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplacePunchout\Api\BuilderInterface;
use Fedex\MarketplacePunchout\Api\CustomerPunchoutUniqueIdRepositoryInterface;
use Fedex\MarketplacePunchout\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Simplexml\Element;
use Magento\Quote\Model\Quote\ItemFactory as QuoteItemFactory;

class Edit implements BuilderInterface
{
    /** @var string  */
    private const VARIANT_QUANTITY = 'Quantity';

    /** @var string  */
    private const VARIANT_ID = 'VariantID';

    /**
     * Shared token punchout integration type.
     */
    private const SHARED_TOKEN_PUNCHOUT_INTEGRATION_TYPE = 'shared-secret-token';

    const TIGER_D_220695 = 'tiger_d220695_cbb_punchout_heartbeat_url';

    /**
     * @param XmlContext $context
     * @param FormKey $formKey
     * @param ProductRepositoryInterface $productRepository
     * @param AuthHelper $authHelper
     * @param QuoteItemFactory $quoteItemFactory
     * @param CustomerPunchoutUniqueIdRepositoryInterface $customerPunchoutUniqueIdRepository
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     */
    public function __construct(
        private XmlContext $context,
        private FormKey $formKey,
        private ProductRepositoryInterface $productRepository,
        protected AuthHelper $authHelper,
        protected QuoteItemFactory $quoteItemFactory,
        protected CustomerPunchoutUniqueIdRepositoryInterface $customerPunchoutUniqueIdRepository,
        protected ToggleConfig                                $toggleConfig,
        private Config $config
    ) {
    }

    /**
     * Build the request xml edit header
     *
     * @param null $productSku
     * @param mixed|null $productConfigData
     * @return Element
     */
    public function build($productSku = null, mixed $productConfigData = null): Element
    {
        $xmlFactory = $this->context->getElementFactory();
        $cookieReader = $this->context->getCookieReaderInterface();
        $customerSession = $this->context->getCustomerSession();
        $config = $this->context->getMarketplaceConfig();
        $requestInterface = $this->context->getRequestInterface();
        $urlBuilder = $this->context->getUrlInterface();
        $product = $this->productRepository->get($requestInterface->getParam('sku'));

        $xml = $xmlFactory->create(
            ['data' => '<Request/>']
        );
        $request = $xml->addChild('PunchOutSetupRequest');
        $request->addAttribute('operation', 'Edit');
        $request->addChild(
            'BuyerCookie',
            $cookieReader->getCookie($customerSession->getName())
        );

        $accountNumber = $config->getAccountNumber();
        $punchoutFlowEnhancement = $fclLoginEnabled = false;

        if ($config->isEnableShopsConnection()) {
            $productSku = $requestInterface->getParam('sku');
            $shopCustomAttributes = $config->getShopCustomAttributesByProductSku($productSku);
            $accountNumber = $shopCustomAttributes['account-number'] ?? $accountNumber;

            if ($shopCustomAttributes['integration-type'] == self::SHARED_TOKEN_PUNCHOUT_INTEGRATION_TYPE) {
                $accountNumber = '';
            }

            if (isset($shopCustomAttributes['fcl-enabled'])) {
                $fclLoginEnabled = $shopCustomAttributes['fcl-enabled'] === 'true';
            }
            if (isset($shopCustomAttributes['punchout-flow-enhancement'])) {
                $punchoutFlowEnhancement = $shopCustomAttributes['punchout-flow-enhancement'] === 'true';
            }
        }

        $request->addChild('Extrinsic', $product->getCategoryPunchout() ? "true" : "false")
            ->addAttribute('name', 'IsCategory');

        if (!empty($accountNumber)) {
            $request->addChild('Extrinsic', $accountNumber)
                ->addAttribute('name', 'AccountNumber');
        }

        $isLoggedIn = $this->authHelper->isLoggedIn();
        $customerSellerConfiguratorUuid = $isLoggedIn
            ? $this->customerPunchoutUniqueIdRepository->retrieveCustomerUniqueId($customerSession->getCustomer())
            : "";
        $request->addChild(
            'Extrinsic', $customerSellerConfiguratorUuid
        )->addAttribute('name', 'UserId');


        // Add CBB related info
        if ($punchoutFlowEnhancement || $fclLoginEnabled) {
            $productInfo = [
                'sku' => $requestInterface->getParam('sku'),
                'offer_id' => $requestInterface->getParam('offer_id'),
                'seller_sku' => $requestInterface->getParam('seller_sku')
            ];

            if ($this->config->isAddingSellerIdInPunchoutEnabled()) {
                $productInfo['seller_id'] = $shopCustomAttributes['shop_id'];
            }

            $productInfo = json_encode($productInfo);

            $request->addChild('Extrinsic', $productInfo)
                ->addAttribute('name', 'ProductConfigData');

            if (!empty($productConfigData)) {
                $request->addChild('Extrinsic', $productConfigData['code_challenge'])
                    ->addAttribute('name', 'CodeChallenge');
            }
        }

        $browser = $request->addChild('BrowserFormPost');
        $browser->addChild(
            'URL',
            $urlBuilder->getUrl(
                'marketplaceproduct/updatecartproduct/index',
                [
                    'sku' => $requestInterface->getParam('sku'),
                    'offer_id' => $requestInterface->getParam('offer_id'),
                    'seller_sku' => $requestInterface->getParam('seller_sku'),
                    'quote_item_id' => $requestInterface->getParam('quote_item_id'),
                    'supplierPartAuxiliaryID' => $requestInterface->getParam('supplier_part_auxiliary_id'),
                    'form_key' => $this->formKey->getFormKey()
                ]
            )
        );

        if ($fclLoginEnabled) {
            $login = $request->addChild('LoginFormPost');
            $login->addChild(
                'URL',
                $urlBuilder->getUrl('marketplacepunchout/index/login')
            );
            if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D_220695)) {
                $heartBeat = $request->addChild('HeartBeatURL');
                $heartBeat->addChild('URL', $urlBuilder->getUrl('timeout/session/heartbeat'));
            }
        }

        $items = $request->addChild('ItemOut');
        $item = $items->addChild('ItemID');
        $item->addChild('SupplierPartID', $requestInterface->getParam('supplier_part_id'));
        $item->addChild(
            'SupplierPartAuxiliaryID',
            $requestInterface->getParam('supplier_part_auxiliary_id')
        );

        /** Adding the variant details to punchout be able to edit the cart */
            $this->extractVariantDetailsFromQuoteItem($requestInterface, $request,$item);


        $request->addChild('Contact');
        return $xml;
    }

    /**
     * @param RequestInterface $requestInterface
     * @param Element|null $simpleXMLElement
     * @param $item
     * @return void
     */
    private function extractVariantDetailsFromQuoteItem(
        RequestInterface $requestInterface,
        ?Element $simpleXMLElement,
        $item
    ): void {
        $quoteItem = $this->quoteItemFactory->create();
        $quoteItem->load($requestInterface->getParam('quote_item_id'));
        if ($quoteItem->getId()) {
            /** Adding the quoteItem additionalData to the itemDetail xml tag */
            $additionalData = json_decode($quoteItem->getAdditionalData(), true);
            $variantDetails = $additionalData['variantDetails'] ?? [];
            $isReorder = (string)$additionalData['can_edit_reorder']??'';
            $intQty = (int)$quoteItem->getQty();
            $quantity = (string)$intQty;
            $this->setVariantsToTheXmlElement($variantDetails, $simpleXMLElement);
            $this->setQtyAndReorderToTheXmlElement($quantity,$isReorder, $item);
        }
    }

    /**
     * @param mixed $variantDetails
     * @param Element|null $simpleXMLElement
     * @return void
     */
    private function setVariantsToTheXmlElement(mixed $variantDetails, ?Element $simpleXMLElement): void
    {
        foreach ($variantDetails as $item) {
            if ($variantId = $item[self::VARIANT_ID] ?? null) {
                $qty = $item[self::VARIANT_QUANTITY] ?? '1';
                $simpleXMLElement->addChild('Extrinsic', $qty)
                    ->addAttribute('name', "{$variantId}_qty");
            }
        }
    }

    /**
     * @param $qty
     * @param $isReorder
     * @param $item
     * @return void
     */
    private function setQtyAndReorderToTheXmlElement($qty, $isReorder,$item): void
    {
        $item->addChild('Extrinsic',$qty)->addAttribute('name', 'Quantity');
        $item->addChild('Extrinsic',$isReorder)->addAttribute('name', 'IsReorder');
    }
}
