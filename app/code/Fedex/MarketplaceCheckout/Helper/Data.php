<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $attributeSetLoaded = [];
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    const FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET = 'FXONonCustomizableProducts';

    /**
     * Xpath re-order error message
     */
    private const XPATH_REORDER_ERROR_MESSAGE = 'environment_toggle_configuration/marketplace_configuration/reorder_error_message';

    /**
     * Xpath enable external product update to quote
     */
    private const XPATH_ENABLE_UPDATE_TO_QUOTE = 'environment_toggle_configuration/environment_toggle/enable_upload_to_quote';

    /**
     * Xpath enable customer shipping account for 3p products
     */
    private const XPATH_ENABLE_CUSTOMER_SHIPPING_ACCOUNT_3P = 'environment_toggle_configuration/environment_toggle/enable_customer_shipping_accounts_third_party';

    /**

     * Xpath enable 3p Discounting
     */
    public const XPATH_ENABLE_3P_DISCOUNTING ='environment_toggle_configuration/environment_toggle/enable_3p_discounting';

    /**
     * Xpath enable vendor specific customer shipping account numbers
     */
    public const XPATH_ENABLE_VENDOR_SHIPPING_ACCOUNT_NUMBERS = 'environment_toggle_configuration/environment_toggle/vendor_shipping_account_number';

    /**
     * Xpath vendor specific customer shipping account numbers disclaimer message
         */
    public const XPATH_VENDOR_SHIPPING_ACCOUNT_NUMBER_MESSAGE = 'fedex/marketplace_configuration/vendor_shipping_account_number_disclaimer';

    /**
     * Xpath review and submit and order confirmation cancellation message
     */
    public const XPATH_REVIEW_SUBMIT_ORDER_CONFIRMATION_CANCELLATION_MESSAGE =
        'fedex/marketplace_configuration/review_and_submit_and_order_confirmation_cancellation_message';

    /**
     * Xpath for enable expected delivery date in order summary section
     */
    public const XPATH_ENABLE_EXPECTED_DELIVERY =
    'environment_toggle_configuration/environment_toggle/sgc_enable_expected_delivery_date';

    /**
     * Xpath enable external product update to quote
     */
    public const XPATH_EPRO_ENABLE_UPDATE_TO_QUOTE = 'environment_toggle_configuration/environment_toggle/explorers_epro_upload_to_quote';

    /**
     * Xpath enable external product update to quote
     */
    public const XPATH_EXPLORERS_D_190723_Fix = 'environment_toggle_configuration/environment_toggle/d_190723_fix';

    /**
     * Xpath for B-2180292 - Add a Limited Time Only tag for Priority Print Pickup
     */
    public const XPATH_PRIORITY_PRINT_LIMITED_TIME_TAG = 'fedex/marketplace_configuration/sgc_priority_print_limited_time_tag';

    /**
     * Xpath for Toggle D-194958 - Reorders for Navitor products are not flowing to Mirakl
     */
    private const XPATH_REORDERS_FOR_NAVITOR_PRODUCTS_ARE_NOT_FLOWING_TO_MIRAKL = 'tiger_194958';

    /**
     * Xpath for Toggle E-458381 - Essendant.
     */
    public const XPATH_ESSENDANT_TOGGLE = 'tiger_e458381_essendant';

    /**
     * Xpath for Toggle D-227706 - Scheduled maintenance page non essendant products.
     */
    public const XPATH_SCHEDULED_MAINTENANCE_PAGE_ESSENDANT_PRODUCTS = 'tiger_d227706_scheduled_maintenance_page_non_essendant_products';

    /**
     * Xpath for Toggle E-422180 - CBB.
     */
    public const XPATH_CBB_TOGGLE = 'tiger_e_422180';

    /**
     * Xpath for Toggle Move Reference from Store to Category Configurations for Product Listing Content.
     */
    public const XPATH_MOVE_REFERENCE_STORE_TO_CATEGORY = 'environment_toggle_configuration/environment_toggle/tiger_d458381_move_reference_store_to_category_for_product_listing_content';

    /* Toggle to check Remove extend life API call for legacy documents in the cart area | B-2353473
     */
    public const REMOVE_LEGACY_DOC_API_CALL_ON_CART = 'techtitans_B2353473_remove_legacy_doc_api_call_on_cart';

    /* Toggle to enable workaround for D-214903
     */
    public const TIGER_D214903 = 'tiger_d214903';
    public const TIGER_D221721 = 'tiger_d221721';
    public const TIGER_D201080 = 'tiger_d201080';

    /**
     * Xpath enable InitializeQuote API Failure Toggle | D-224874
     */
    public const XPATH_ENABLE_D_224874 = 'environment_toggle_configuration/environment_toggle/magegeeks_d224874';

    /**
     * Xpath enable Unable to send Navitor/Printful order to Mirakl
     */
    public const XPATH_ENABLE_D_216694 = 'environment_toggle_configuration/environment_toggle/tiger_d216694';


    public const TIGER_TEAM_D_232505 = 'environment_toggle_configuration/environment_toggle/tiger_team_D_232505';
    /**
     * Construct
     *
     * @param ToggleConfig $toggleConfig
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        private AttributeSetRepositoryInterface $attributeSetRepository
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * Get re-order error message
     * @return bool
     */
    public function getReorderErrorMessage(): string
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XPATH_REORDER_ERROR_MESSAGE) ?? '';
    }

    /**
     * Gets status of enable to quote.
     * @return bool
     */
    public function isUploadToQuoteEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_UPDATE_TO_QUOTE);
    }

    /**
     * Gets status of enable customer shipping account for third party products
     * @return bool
     */
    public function isCustomerShippingAccount3PEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_CUSTOMER_SHIPPING_ACCOUNT_3P);
    }


    /**
     * Gets status of enable cart integration printful.
     * @return bool
     */
    public function isCartIntegrationPrintfulEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_CART_INTEGRATION_PRINTFUL);
    }

    /**
     * Gets status of enable essendant toggle.
     * @return bool
     */
    public function isEssendantToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_ESSENDANT_TOGGLE);
    }

    /**
     * Gets status of enable scheduled maintenance page essendant products toggle.
     * @return bool
     */
    public function isScheduledMaintenancePageEssendantProductsToggle(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_SCHEDULED_MAINTENANCE_PAGE_ESSENDANT_PRODUCTS);
    }

    /**
     * Gets status of enable CBB toggle.
     * @return bool
     */
    public function isCBBToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::XPATH_CBB_TOGGLE);
    }

    /**
     * Gets status of enable move reference from store to category toggle.
     * @return bool
     */
    public function isMoveReferenceFromStoreToCategoryToggleEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_MOVE_REFERENCE_STORE_TO_CATEGORY);
    }

    /**
     * Gets status of enable vendor specific customer shipping account for third party products
     * @return bool
     */
    public function isVendorSpecificCustomerShippingAccountEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_VENDOR_SHIPPING_ACCOUNT_NUMBERS);
    }

    /**
     * Gets vendor specific customer shipping account disclaimer message
     * @return string
     */
    public function getVendorSpecificCustomerShippingAccountDisclaimer(): string
    {
        return (string) $this->toggleConfig->getToggleConfig(self::XPATH_VENDOR_SHIPPING_ACCOUNT_NUMBER_MESSAGE) ?? '';
    }

    /**
     * Gets cancellation message for Review and Submit and Order Confirmation pages
     *
     * @return string
     */
    public function getReviewSubmitAndOrderConfirmationCancellationMessage(): string
    {
        return (string) $this->toggleConfig->getToggleConfig(
            self::XPATH_REVIEW_SUBMIT_ORDER_CONFIRMATION_CANCELLATION_MESSAGE
        ) ?? '';
    }

    /**
     * Toggle for B-2082381 - Expected Delivery Date in Order Summary
     *
     * @return string
     */
    public function isExpectedDeliveryDateEnabled(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_EXPECTED_DELIVERY);
    }

    /***
     * Get toggle for Explorers EPRO U2Q
     * @return bool
     */
    public function isEproUploadToQuoteEnable(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::XPATH_EPRO_ENABLE_UPDATE_TO_QUOTE);
    }

    /***
     * Get toggle for Explorers D_190723_Fix
     * @return bool
     */
    public function isD190723FixToggleEnable(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::XPATH_EXPLORERS_D_190723_Fix);
    }

    /**
     * Toggle for B-2180292 - Add a Limited Time Only tag for Priority Print Pickup
     *
     * @return bool
     */
    public function isPriorityPrintLimitedTimeTag(): bool
    {
        return (bool)$this->toggleConfig->getToggleConfig(self::XPATH_PRIORITY_PRINT_LIMITED_TIME_TAG);
    }

    /**
     * Get toggle for D-194958 - Reorders for Navitor products are not flowing to Mirakl
     * @return bool
     */
    public function getD194958(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(
            self::XPATH_REORDERS_FOR_NAVITOR_PRODUCTS_ARE_NOT_FLOWING_TO_MIRAKL
        );
    }

    /**
     * @param $superAttributes
     * @return array|void
     */
    public function getSuperAttributeArray($superAttributes)
    {
        $result = [];
        if ($this->isEssendantToggleEnabled()) {
            $superAttributesArray = trim($superAttributes, '[]');
            $keyValuePairs = explode(',', $superAttributesArray);
            foreach ($keyValuePairs as $valueArray) {
                list($key, $value) = explode('=>', $valueArray);
                $result[intval($key)] = intval($value);
            }
            return $result;
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function recursiveAdjustArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveAdjustArray($value);
            }
            elseif (is_numeric($value)) {
                $data[$key] = (string)$value;
            }
            elseif (is_bool($value)) {
                $data[$key] = $value ? '1' : '0';
            }
            elseif ($value === '' || $value === null) {
                $data[$key] = '';
            }
            else {
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * Create a root tag after the xml tag, to be a valid XML.
     *
     * @param array $data
     * @return array[]
     */
    public function adjustArrayForXml(array $data): array
    {
        $adjustedData = $this->recursiveAdjustArray($data);

        return [
            'shipping_information' => $adjustedData
        ];
    }

    /**
     * Check if any item in the quote session has a legacy document.
     *
     * @return bool
     */
    public function hasLegacyDocumentInQuoteSession()
    {
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllVisibleItems();

        return $this->hasLegacyDocumentInCartItems($items);
    }

    /**
     * Check if any item in the given list has a legacy document.
     *
     * @param Item[] $items
     * @return bool
     */
    public function hasLegacyDocumentInCartItems($items)
    {
        foreach ($items as $item) {
            if ($this->checkItemIsLegacyDocument($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an item has a legacy document based on content references.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool True if a legacy document exists, false otherwise.
     */
    public function checkItemIsLegacyDocument($item)
    {
        try {
            $buyRequestOption = $item->getOptionByCode('info_buyRequest');

            if (!$buyRequestOption || !$buyRequestOption->getValue()) {
                return false;
            }

            $productData = json_decode($buyRequestOption->getValue(), true);

            $externalProd = $productData['external_prod'] ?? null;
            $contentAssociations = $externalProd[0]['contentAssociations'] ?? null;

            if (is_array($externalProd) && is_array($contentAssociations)) {
                foreach ($productData['external_prod'][0]['contentAssociations'] as $contentAssociation) {
                    if (!empty($contentAssociation['contentReference']) && is_numeric($contentAssociation['contentReference'])) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ' - Exception occurred while checking legacy document: ' . $e->getMessage());
        }
        return false;

    }

   /**
    * Toggle to check Remove extend life API call for legacy documents in the cart area | B-2353473
    */
   public function checkLegacyDocApiOnCartToggle()
   {
       return (bool)$this->toggleConfig->getToggleConfigValue(self::REMOVE_LEGACY_DOC_API_CALL_ON_CART);
   }

   /**
    * Toggle to check D-214903 Groot: Shopping cart page is broken while adding a CBB item to cart in a new session
    */
   public function isToggleD214903Enabled()
   {
       return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D214903);
   }

    /**
     * Toggle to check D-214903 Groot: Shopping cart page is broken while adding a CBB item to cart in a new session
     */
    public function isToggleD221721Enabled()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(self::TIGER_D221721);
    }

    /**
     * @param Quote|Order $quoteOrder
     * @return bool
     */
   public function checkIfItemsAreAllNonCustomizableProduct(Quote|Order $quoteOrder): bool
   {
       foreach ($quoteOrder->getItemsCollection() as $item) {
           /** @var Product $product */
           $product = $item->getProduct();
           $attributeSetName = $this->loadAttributeSet($product->getAttributeSetId());
           if($attributeSetName != self::FXO_NON_CUSTOMIZABLE_PRODUCTS_ATTR_SET) {
               return false;
           }
       }

       return true;
   }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
   public function getQuote()
   {
       return $this->checkoutSession->getQuote();
   }

   private function loadAttributeSet($attributeSetId): ?string
   {
       if (isset($this->attributeSetLoaded[$attributeSetId])) {
           return $this->attributeSetLoaded[$attributeSetId];
       }

       try {
           $attributeSet = $this->attributeSetRepository->get($attributeSetId);
           $this->attributeSetLoaded[$attributeSetId] = $attributeSet->getAttributeSetName();
           return $this->attributeSetLoaded[$attributeSetId];
       } catch (\Exception $e) {
           $this->logger->error('Error loading attribute set: ' . $e->getMessage());
           return null;
       }
   }

   /***
     * Get toggle for Initialize Quote API Failure
     * @return bool
     */
    public function isD224874Enable(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_D_224874);
    }

    /***
     * Get toggle for Unable to send Navitor/Printful order to Mirakl
     * @return bool
     */
    public function isD216694Enable(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::XPATH_ENABLE_D_216694);
    }

    /***
     * Get toggle for Tiger Team D-232505 - Additional message shown on PDP for Simple 3p products
     * @return bool
     */
    public function isTigerTeamD232505ToggleEnabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfig(self::TIGER_TEAM_D_232505);
    }

    /**
     * @return bool
     */
    public function isToggleD201080Enabled(): bool
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TIGER_D201080);
    }

}
