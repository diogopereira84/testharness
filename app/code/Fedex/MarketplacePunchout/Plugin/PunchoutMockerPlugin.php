<?php
declare (strict_types = 1);

namespace Fedex\MarketplacePunchout\Plugin;

use Fedex\MarketplaceProduct\Model\AddToCartContext;
use Fedex\MarketplacePunchout\Controller\Index\Index as PunchoutController;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Store\Model\ScopeInterface;


class PunchoutMockerPlugin
{
    public const XML_PATH_PUNCHOUT_MOCKER_ENABLED       = 'fedex/marketplace_configuration/mock_payload_enabled';
    public const XML_PATH_PUNCHOUT_MOCKER_SKUS          = 'fedex/marketplace_configuration/mock_payload_skus';
    public const XML_PATH_PUNCHOUT_MOCK_PAYLOAD         = 'fedex/marketplace_configuration/mock_payload';
    public const XML_PATH_B2B_PRINT_PRODUCT_CATEGORY    = 'ondemand_setting/category_setting/epro_print';

    /**
     * @param AddToCartContext $context
     * @param ScopeConfigInterface $scopeConfig
     * @param MarketplaceConfig $marketplaceConfig
     * @param FormKey $formKey
     */
    public function __construct(
        private readonly AddToCartContext       $context,
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly MarketplaceConfig    $marketplaceConfig,
        protected readonly FormKey              $formKey
    ) {
    }

    /**
     * @param PunchoutController $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(PunchoutController $subject, callable $proceed)
    {
        if ($this->isPunchoutMockerEnabled()) {
            $request = $this->context->getRequestInterface();
            $productSku = $request->getParam('sku');
            if ($this->isProductInSkusList($productSku)) {
                $payload = $this->getMockPayload();
                if (!empty($payload)) {
                    $request->setParams([
                        'cxml-urlencoded' => $payload,
                        'sku' => $productSku,
                        'offer_id' => $request->getParam('offer_id'),
                        'seller_sku' => $request->getParam('seller_sku'),
                        'form_key' => $this->formKey->getFormKey()
                    ]);

                    $params = [
                        'sku' => $productSku,
                        'qty' => 1,
                        'isMarketplaceProduct' => true
                    ];

                    $this->context->getQuoteProductAdd()->addItemToCart($params);

                    $resultRedirect = $this->context->getRedirectFactory()->create();
                    return $resultRedirect->setPath('checkout/cart');
                }
            }
        }

        return $proceed();
    }

    /**
     * @return bool
     */
    public function isPunchoutMockerEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_PUNCHOUT_MOCKER_ENABLED);
    }

    /**
     * @param $productSku
     * @return bool
     */
    public function isProductInSkusList($productSku): bool
    {
        $skus = $this->scopeConfig->getValue(self::XML_PATH_PUNCHOUT_MOCKER_SKUS);
        if (empty($skus)) {
            return false;
        }

        $skus = explode(',', $skus);
        return in_array($productSku, $skus);
    }

    /**
     * @return string|null
     */
    public function getMockPayload(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PUNCHOUT_MOCK_PAYLOAD);
    }

    /**
     * @return string|null
     */
    public function getB2bPrintProductsCategory(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_B2B_PRINT_PRODUCT_CATEGORY,
            ScopeInterface::SCOPE_STORE
        );
    }
}
