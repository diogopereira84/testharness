<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Model\Address\Builder;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\TotalsCollector;
use Fedex\Cart\Model\Quote\IntegrationItem\Repository as IntegrationItemRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\Stdlib\DateTime;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\Cart\Model\Quote\Integration\Command\SaveRetailCustomerIdInterface;
use Magento\Framework\App\Request\Http;
use Exception;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\NewRelicHeaders;

class CreateOrUpdateOrder extends AbstractResolver
{
    /** @var string  */
    const MAPPED_ORGANIZATION_ATTRIBUTE = 'company';

    /** @var string  */
    const ORGANIZATION_KEY = 'organization';

    const CART_ID = 'cart_id';

    const ALTERNATE_CONTACT = 'alternate_contact';

    const FIRST_NAME = 'firstname';

    const LAST_NAME = 'lastname';

    const EMAIL = 'email';

    const TELEPHONE = 'telephone';

    /**
     * STREET
     */
    public const STREET = 'street';

    /**
     * REGION_ID
     */
    public const REGION_ID = 'region_id';

    /**
     * COMPANY
     */
    public const COMPANY = 'company';

    public const RETAIL_CUSTOMER_ID = 'retail_customer_id';

    public const INPUT = 'input';
    public const PICKUP_DATA = 'pickup_data';
    public const EXT_KEY = 'ext';
    public const CONTACT_INFORMATION = 'contact_information';
    public const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    public const FEDEX_SHIP_ACCOUNT_NUMBER = 'fedex_ship_account_number';
    public const LTE_IDENTIFIER = 'lte_identifier';
    public const CONTACT_INFORMATION_KEY = 'contactInformation';
    public const REQUESTED_PICKUP_LOCAL_TIME = 'requested_pickup_local_time';
    public const PICKUP_LOCATION_DATE = 'pickup_location_date';
    public const NOTES = 'notes';
    public const ORDER_NOTES = 'order_notes';
    public const FXO_PRODUCT_INSTANCE = 'fxoProductInstance';
    public const PRODUCT_RATE_TOTAL = 'productRateTotal';
    public const UNIT_OF_MEASURE = 'unitOfMeasure';
    public const PRODUCT_CONFIG = 'productConfig';
    public const PRODUCT = 'product';
    public const PRICEABLE = 'priceable';
    public const IS_EDITABLE = 'isEditable';
    public const PRODUCT_LINE_PRICE = 'productLinePrice';
    public const PRODUCT_RETAIL_PRICE = 'productRetailPrice';
    public const INSTANCE_ID_KEY = 'instance_id';
    public const PRODUCT_ID_KEY = 'product_id';
    public const NAME_KEY = 'name';
    public const USER_PRODUCT_NAME_KEY = 'user_product_name';
    public const RETAIL_PRICE_KEY = 'retail_price';
    public const DISCOUNT_AMOUNT_KEY = 'discount_amount';
    public const UNITY_QUANTITY_KEY = 'unity_quantity';
    public const LINE_PRICE_KEY = 'line_price';
    public const PRODUCT_LINE_PRICE_KEY = 'product_line_price';
    public const UNIT_OF_MEASURE_KEY = 'unit_of_measure';
    public const PRICEABLE_KEY = 'priceable';
    public const EDITABLE_KEY = 'editable';
    public const PICKUP_LOCATION_ID = 'pickup_location_id';
    public const PICKUP_STORE_ID = 'pickup_store_id';
    public const STORE_ID = 'store_id';
    public const LOCATION_ID = 'location_id';
    public const CURRENCY = 'currency';
    public const CURRENCY_CODE = 'USD';
    public const RATE_DETAILS = 'rate_details';
    public const GROSS_AMOUNT = 'gross_amount';
    public const TOTAL_DISCOUNT_AMOUNT = 'total_discount_amount';
    public const NET_AMOUNT = 'net_amount';
    public const TAXABLE_AMOUNT = 'taxable_amount';
    public const TAX_AMOUNT = 'tax_amount';
    public const TOTAL_AMOUNT = 'total_amount';
    public const GTN_KEY = 'gtn';
    public const CART_KEY = 'cart';
    public const CITY_KEY = 'city';
    public const COUNTRY_ID = 'country_id';
    public const POSTCODE = 'postcode';
    public const PICKUP_LOCATION_STREET = 'pickup_location_street';
    public const PICKUP_LOCATION_CITY = 'pickup_location_city';
    public const PICKUP_LOCATION_COUNTRY = 'pickup_location_country';
    public const PICKUP_LOCATION_ZIPCODE = 'pickup_location_zipcode';

    /**
     * @var
     */
    private $cart;

    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param CartRepositoryInterface $cartRepository
     * @param Builder $addressBuilder
     * @param FXORateQuote $fxoRateQuote
     * @param IntegrationItemRepository $integrationItemRepository
     * @param AddressFactory $addressFactory
     * @param DateTime $dateTime
     * @param InstoreConfig $instoreConfig
     * @param SaveRetailCustomerIdInterface $saveRetailCustomerId
     * @param Http $request
     * @param TotalsCollector $totalsCollector
     * @param SetFedexAccountNumber $setFedexAccountNumber
     * @param RequestQueryValidator $requestQueryValidator
     * @param Cart $cartModel
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationBatchComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        protected CartRepositoryInterface                   $cartRepository,
        private readonly Builder                            $addressBuilder,
        private readonly FXORateQuote                       $fxoRateQuote,
        private readonly IntegrationItemRepository          $integrationItemRepository,
        private readonly AddressFactory                     $addressFactory,
        private readonly DateTime                           $dateTime,
        private readonly InstoreConfig                      $instoreConfig,
        private readonly SaveRetailCustomerIdInterface      $saveRetailCustomerId,
        private readonly Http                               $request,
        private readonly TotalsCollector                    $totalsCollector,
        private readonly SetFedexAccountNumber              $setFedexAccountNumber,
        private readonly RequestQueryValidator              $requestQueryValidator,
        private readonly Cart                               $cartModel,
        RequestCommandFactory                               $requestCommandFactory,
        BatchResponseFactory                                $batchResponseFactory,
        LoggerHelper                                        $loggerHelper,
        ValidationBatchComposite                            $validationComposite,
        NewRelicHeaders                                     $newRelicHeaders,
        array                                               $validations = []
    ) {
        parent::__construct(
            $requestCommandFactory,
            $batchResponseFactory,
            $loggerHelper,
            $validationComposite,
            $newRelicHeaders,
            $validations
        );
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     * @throws GraphQlInputException
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        try {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            $response = $this->batchResponseFactory->create();

            foreach ($requests as $request) {
                $args = $request->getArgs();
                $this->cart = $this->cartModel->getCart($args[self::INPUT][self::CART_ID], $context);

                $contactInformation = $args[self::INPUT][self::CONTACT_INFORMATION];
                $hasFedexAccountNumber = isset($contactInformation[self::FEDEX_ACCOUNT_NUMBER]);
                $hasFedexShipAccountNumber = isset($contactInformation[self::FEDEX_SHIP_ACCOUNT_NUMBER]);
                $hasLteIdentifier = isset($contactInformation[self::LTE_IDENTIFIER]);
                $alternateContact = $contactInformation[self::ALTERNATE_CONTACT] ?? null;
                $pickupData = $args[self::INPUT][self::PICKUP_DATA];
                $shippingContact = $contactInformation;
                if ((isset($contactInformation[self::ALTERNATE_CONTACT])) && (!empty($contactInformation[self::ALTERNATE_CONTACT]))) {
                    $shippingContact = $contactInformation[self::ALTERNATE_CONTACT];
                }

                $this->request->setParam(self::CONTACT_INFORMATION_KEY, $contactInformation);

                if (key_exists(self::ORGANIZATION_KEY, $contactInformation)) {
                    $shippingContact[self::MAPPED_ORGANIZATION_ATTRIBUTE] = $contactInformation[self::ORGANIZATION_KEY];
                }

                $quoteShip = $this->cart->getShippingAddress();
                $this->setContactInfo($quoteShip, $shippingContact);

                if (!key_exists(self::ORGANIZATION_KEY, $contactInformation)) {
                    $contactInformation[self::ORGANIZATION_KEY] = $quoteShip->getCompany();
                }
                $quoteShip->save();

                $this->inStoreUpdateShippingAddress($this->cart, $pickupData, $shippingContact);
                $this->addressBuilder->setAddressData($this->cart, $shippingContact, $pickupData);
                $this->cartModel->setCustomerCartData($this->cart, $shippingContact, $alternateContact);
                $rate_details = [];
                $totalNetAmount = 0;
                $totalTaxAmount = 0;
                $grossAmount = 0;
                $totalDiscountAmount = 0;
                $taxableAmount = 0;
                $integration = $this->cartIntegrationRepository->getByQuoteId($this->cart->getId());
                $this->saveRetailCustomerId->execute($integration, $contactInformation[self::RETAIL_CUSTOMER_ID]);
                $this->cart->setData(
                    self::REQUESTED_PICKUP_LOCAL_TIME,
                    $pickupData[self::PICKUP_LOCATION_DATE] ?? null
                );

                $orderNotes = isset($args[self::INPUT][self::NOTES]) ? json_encode($args[self::INPUT][self::NOTES]) : null;
                $this->cart->setData(self::ORDER_NOTES, $orderNotes);
                if ($this->instoreConfig->isAddOrUpdateFedexAccountNumberEnabled() && ($hasFedexAccountNumber || $hasFedexShipAccountNumber)) {
                    $fedexAccountNumber = $contactInformation[self::FEDEX_ACCOUNT_NUMBER] ?? null;
                    $fedexShipAccountNumber =  $contactInformation[self::FEDEX_SHIP_ACCOUNT_NUMBER] ?? null;
                    $lteIdentifier =  $contactInformation[self::LTE_IDENTIFIER] ?? null;
                    if ($this->instoreConfig->isSupportLteIdentifierEnabled() && $hasLteIdentifier) {
                        $lteIdentifier =  $contactInformation[self::LTE_IDENTIFIER];
                    }
                    $this->cart->setLteIdentifier($lteIdentifier);
                    $this->setFedexAccountNumber->setFedexAccountNumber($fedexAccountNumber, $fedexShipAccountNumber, $this->cart);
                }
                try {
                    $this->fxoRateQuote->getFXORateQuote($this->cart);
                } catch (GraphQlFujitsuResponseException $e) {
                    $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on getting FXO Rate Quote. ' . $e->getMessage(), $headerArray);
                    throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                }

                $this->addressBuilder->setAddressData($this->cart, $shippingContact, $pickupData);
                $this->inStoreSavePickupLocationDate($integration, $pickupData, $headerArray);
                $shippingAddress = $this->cart->getShippingAddress();
                if ((!empty($shippingAddress)) && (!empty($shippingAddress->getCountryId()))) {
                    $this->cartRepository->save($this->cart);
                    $this->addressBuilder->setAddressData($this->cart, $shippingContact, $pickupData);
                }

                foreach ($this->cart->getAllVisibleItems() as $item) {
                    $product = $item->getProduct();
                    try {
                        $integrationItemRepository = $this->integrationItemRepository->getByQuoteItemId((int) $item->getId());
                        $itemData = $integrationItemRepository->getItemData();
                        $fxoItemData = json_decode($itemData, true);
                        $unitOfMeasure = $fxoItemData[self::FXO_PRODUCT_INSTANCE][self::PRODUCT_RATE_TOTAL][self::UNIT_OF_MEASURE] ?? null;
                        $priceable = $fxoItemData[self::FXO_PRODUCT_INSTANCE][self::PRODUCT_CONFIG][self::PRODUCT][self::PRICEABLE] ?? null;
                        $editable = $fxoItemData[self::FXO_PRODUCT_INSTANCE][self::IS_EDITABLE] ?? null;
                    } catch (NoSuchEntityException $e) {
                        $unitOfMeasure = null;
                        $priceable = null;
                        $editable = null;
                        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Error getting item data: ' . $e->getMessage(), $headerArray);
                    }

                    if ($this->requestQueryValidator->isGraphQl()) {
                        $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                        $productLinePrice = $additionalData[self::PRODUCT_LINE_PRICE];
                        $productRetailPrice = $additionalData[self::PRODUCT_RETAIL_PRICE];
                        $productDiscount = $item->getDiscount();
                    } else {
                        $productLinePrice = $item->getRowTotal();
                        $productRetailPrice = $item->getPrice();
                        $productDiscount = $item->getDiscountAmount();

                        $grossAmount += $item->getRowTotal();
                        $totalTaxAmount += $item->getTaxAmount();
                        $totalDiscountAmount += $item->getDiscountAmount();
                    }
                    $totalNetAmount = $this->cart->getGrandTotal();

                    $rate_details[] = [
                        self::INSTANCE_ID_KEY => $item->getInstanceId(),
                        self::PRODUCT_ID_KEY => $item->getProductId(),
                        self::NAME_KEY => $product->getName(),
                        self::USER_PRODUCT_NAME_KEY => $product->getName(),
                        self::RETAIL_PRICE_KEY => floatval($productRetailPrice),
                        self::DISCOUNT_AMOUNT_KEY => floatval($productDiscount),
                        self::UNITY_QUANTITY_KEY => floatval($item->getQty()),
                        self::LINE_PRICE_KEY => floatval($productLinePrice),
                        self::PRODUCT_LINE_PRICE_KEY => floatval($productLinePrice),
                        self::UNIT_OF_MEASURE_KEY => $unitOfMeasure,
                        self::PRICEABLE_KEY => $priceable,
                        self::EDITABLE_KEY => $editable,
                    ];
                }

                if ($this->requestQueryValidator->isGraphQl()) {
                    $cartTotals = $this->totalsCollector->collectQuoteTotals($this->cart);

                    $netAmount = $integration->getRaqNetAmount();
                    $grossAmount = $cartTotals->getSubtotal();
                    $totalTaxAmount = $cartTotals->getCustomTaxAmount();
                    $totalDiscountAmount = $cartTotals->getFedexDiscountAmount();
                } else {
                    $netAmount = $this->cart->getGrandTotal() - $this->cart->getCustomTaxAmount();
                }

                $integration->setPickupLocationId($pickupData[self::PICKUP_LOCATION_ID] ?? null);
                $integration->setPickupStoreId($pickupData[self::PICKUP_STORE_ID] ?? null);
                $this->cartIntegrationRepository->save($integration);

                $alternateContactResponse = $alternateContact ?
                    [
                        self::FIRST_NAME => $alternateContact[self::FIRST_NAME],
                        self::LAST_NAME => $alternateContact[self::LAST_NAME],
                        self::EMAIL => $alternateContact[self::EMAIL],
                        self::TELEPHONE => $alternateContact[self::TELEPHONE],
                        self::EXT_KEY => $alternateContact[self::EXT_KEY]
                    ] : null;

                $data = [
                    self::STORE_ID => $integration->getStoreId(),
                    self::LOCATION_ID => $integration->getLocationId(),
                    self::CURRENCY => self::CURRENCY_CODE,
                    self::CONTACT_INFORMATION => [
                        self::FIRST_NAME => $contactInformation[self::FIRST_NAME],
                        self::LAST_NAME => $contactInformation[self::LAST_NAME],
                        self::EMAIL => $contactInformation[self::EMAIL],
                        self::TELEPHONE => $contactInformation[self::TELEPHONE],
                        self::EXT_KEY => $contactInformation[self::EXT_KEY],
                        self::FEDEX_ACCOUNT_NUMBER => $fedexAccountNumber ?? null,
                        self::FEDEX_SHIP_ACCOUNT_NUMBER => $fedexShipAccountNumber ?? null,
                        self::ALTERNATE_CONTACT => $alternateContactResponse
                    ],
                    self::RATE_DETAILS => $rate_details,
                    self::GROSS_AMOUNT => $grossAmount,
                    self::TOTAL_DISCOUNT_AMOUNT => $totalDiscountAmount,
                    self::NET_AMOUNT => $netAmount,
                    self::TAXABLE_AMOUNT => $taxableAmount,
                    self::TAX_AMOUNT => $totalTaxAmount,
                    self::TOTAL_AMOUNT => $totalNetAmount,
                    self::GTN_KEY => $this->cart->getGtn()
                ];

                $data[self::CONTACT_INFORMATION][self::ORGANIZATION_KEY] =
                    $contactInformation[self::ORGANIZATION_KEY] ?? $quoteShip->getCompany();

                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);

                $response->addResponse($request, [self::CART_KEY => $data]);
            }
            return $response;
        } catch (Exception $e) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on saving information into cart. ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
            throw new GraphQlInputException(__('Error on saving information into cart ' . $e->getMessage()));
        }
    }

    /**
     * Update shipping address on cart
     *
     * @param Quote $quote
     * @param array $pickupData
     * @param array $shippingContact
     * @return void
     * @throws Exception
     */
    private function inStoreUpdateShippingAddress(Quote $quote, array $pickupData, array $shippingContact): void
    {
        if (empty($pickupData)) {
            return;
        }

        $shippingAddress = $quote->getShippingAddress();
        if (empty($shippingAddress)) {
            $shippingAddress = $this->addressFactory->create();
        }

        $shippingAddress->addData([
            self::FIRST_NAME => $shippingContact[self::FIRST_NAME],
            self::LAST_NAME => $shippingContact[self::LAST_NAME],
            self::STREET => $pickupData[self::PICKUP_LOCATION_STREET],
            self::CITY_KEY => $pickupData[self::PICKUP_LOCATION_CITY],
            self::COUNTRY_ID => $pickupData[self::PICKUP_LOCATION_COUNTRY],
            self::POSTCODE => $pickupData[self::PICKUP_LOCATION_ZIPCODE],
            self::TELEPHONE => $shippingContact[self::TELEPHONE],
        ]);

        $quote->setShippingAddress($shippingAddress);
        $quote->save();
    }

    /**
     * Save pickup location date on quote integration
     *
     * @param CartIntegrationInterface $integration
     * @param array $pickupData
     * @param array $headerArray
     * @return void
     */
    private function inStoreSavePickupLocationDate(CartIntegrationInterface $integration, array $pickupData, array $headerArray): void
    {
        if (empty($pickupData)) {
            return;
        }

        try {
            $formatPickupLocationDate = $this->dateTime->formatDate($pickupData['pickup_location_date'], true);
            $integration->setPickupLocationDate($formatPickupLocationDate);
            $this->cartIntegrationRepository->save($integration);
        } catch (Exception $e) {
            $this->loggerHelper->error(
                __METHOD__ . ':' . __LINE__ . ' Error on saving data on quote integration. ' . $e->getMessage(),
                $headerArray
            );
        }
    }

    /**
     * @param Address $item
     * @param array $shippingContact
     * @return void
     */
    public function setContactInfo($item, array $shippingContact)
    {
        $this->cartModel->setContactInfo($item, $shippingContact);
        if (key_exists(self::MAPPED_ORGANIZATION_ATTRIBUTE, $shippingContact)
        ) {
            $item->setCompany($shippingContact[self::MAPPED_ORGANIZATION_ATTRIBUTE]);
        }
    }
}
