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
use Fedex\MarketplaceProduct\Model\NonCustomizableProduct;
use Fedex\MarketplacePunchout\Api\BuilderInterface;
use Fedex\MarketplacePunchout\Api\CustomerPunchoutUniqueIdRepositoryInterface;
use Fedex\MarketplacePunchout\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Simplexml\Element;

class Create implements BuilderInterface
{
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
     * @param CustomerPunchoutUniqueIdRepositoryInterface $customerPunchoutUniqueIdRepository
     * @param NonCustomizableProduct $nonCustomizableProductModel
     * @param Session $customerSession
     * @param ToggleConfig $toggleConfig
     * @param Config $config
     */
    public function __construct(
        private XmlContext                                    $context,
        private FormKey                                       $formKey,
        private ProductRepositoryInterface                    $productRepository,
        protected AuthHelper                                  $authHelper,
        protected CustomerPunchoutUniqueIdRepositoryInterface $customerPunchoutUniqueIdRepository,
        private NonCustomizableProduct                        $nonCustomizableProductModel,
        private Session                                       $customerSession,
        protected ToggleConfig                                $toggleConfig,
        private Config                                        $config
    ) {
    }

    /**
     * Build the request xml create header
     *
     * @param null $productSku
     * @param mixed|null $productConfigData
     * @return Element
     * @throws LocalizedException
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

        $accountNumber = $config->getAccountNumber();
        $punchoutFlowEnhancement = $fclLoginEnabled = false;

        if ($config->isEnableShopsConnection()){
            $productSku           = $requestInterface->getParam('sku');
            $shopCustomAttributes = $config->getShopCustomAttributesByProductSku($productSku);
            $accountNumber        = $shopCustomAttributes['account-number'] ?? $accountNumber;

            if ($shopCustomAttributes['integration-type'] == self::SHARED_TOKEN_PUNCHOUT_INTEGRATION_TYPE) {
                $accountNumber = '';
            }

            if ($this->nonCustomizableProductModel->isMktCbbEnabled()) {
                if (isset($shopCustomAttributes['fcl-enabled'])) {
                    $fclLoginEnabled = $shopCustomAttributes['fcl-enabled'] === 'true';
                }
                if (isset($shopCustomAttributes['punchout-flow-enhancement'])) {
                    $punchoutFlowEnhancement = $shopCustomAttributes['punchout-flow-enhancement'] === 'true';
                }
            }
        }

        $xml = $xmlFactory->create(
            ['data' => '<Request/>']
        );
        $request = $xml->addChild('PunchOutSetupRequest');

        $request->addAttribute('operation', 'Create');
        $request->addChild(
            'BuyerCookie',
            $cookieReader->getCookie($customerSession->getName())
        );
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

        $request->addChild('Extrinsic')
            ->addAttribute('name', 'IsReorder');

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

            if ($fclLoginEnabled) {
                $configurationData = $this->customerSession->getSellerConfigurationData();
                $request->addChild('Extrinsic', $configurationData ?? '')
                    ->addAttribute('name', 'ConfigurationData');
            }

            if (!empty($productConfigData)) {
                $request->addChild('Extrinsic', $productConfigData['code_challenge'])
                    ->addAttribute('name', 'CodeChallenge');
            }

            $this->customerSession->unsSellerConfigurationData();
            $this->customerSession->unsProductConfigData();
        }

        $browser = $request->addChild('BrowserFormPost');
        $browser->addChild(
            'URL',
            $urlBuilder->getUrl(
                'marketplaceproduct/addtocart/index',
                [
                    'sku' => $requestInterface->getParam('sku'),
                    'offer_id' => $requestInterface->getParam('offer_id'),
                    'seller_sku' => $requestInterface->getParam('seller_sku'),
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

        $items = $request->addChild('SelectedItem');
        $item = $items->addChild('ItemID');
        $item->addChild('SupplierPartID', $requestInterface->getParam('seller_sku'));
        $item->addChild('SupplierPartAuxiliaryID');
        $item->addChild('Extrinsic')->addAttribute('name', 'Quantity');
        $request->addChild('Contact');
        return $xml;
    }
}
