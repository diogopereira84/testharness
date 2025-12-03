<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Pedro Basseto <pbasseto@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Model\Quote\Integration\Command\SaveRetailCustomerIdInterface;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\App\Request\Http;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\GraphQl\Model\NewRelicHeaders;

class UpdateGuestCartContactInformation extends AbstractResolver
{
    const CART_ID = 'cart_id';

    const ALTERNATE_CONTACT = 'alternate_contact';

    const FIRST_NAME = 'firstname';

    const LAST_NAME = 'lastname';

    const EMAIL = 'email';

    const TELEPHONE = 'telephone';

    /** @var string  */
    const MAPPED_ORGANIZATION_ATTRIBUTE = 'company';

    /** @var string  */
    const ORGANIZATION_KEY = 'organization';

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
    public const EXT_KEY = 'ext';
    public const CONTACT_INFORMATION = 'contact_information';
    public const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    public const FEDEX_SHIP_ACCOUNT_NUMBER = 'fedex_ship_account_number';
    public const LTE_IDENTIFIER = 'lte_identifier';
    public const HAS_ALTERNATE_PERSON = 'has_alternate_person';
    public const CONTACT_INFORMATION_KEY = 'contactInformation';

    /**
     * @var
     */
    private $cart;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param SaveRetailCustomerIdInterface $saveRetailCustomerId
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param InstoreConfig $config
     * @param Http $request
     * @param FXORateQuote $fxoRateQuote
     * @param SetFedexAccountNumber $setFedexAccountNumber
     * @param Cart $cartModel
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationBatchComposite $validationComposite
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param JsonSerializer $jsonSerializer
     * @param array $validations
     */
    public function __construct(
        private readonly CartRepositoryInterface            $cartRepository,
        private readonly SaveRetailCustomerIdInterface      $saveRetailCustomerId,
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private readonly InstoreConfig                      $config,
        private readonly Http                               $request,
        private readonly FXORateQuote                       $fxoRateQuote,
        private readonly SetFedexAccountNumber              $setFedexAccountNumber,
        private readonly Cart                               $cartModel,
        RequestCommandFactory                               $requestCommandFactory,
        ValidationBatchComposite                            $validationComposite,
        BatchResponseFactory                                $batchResponseFactory,
        LoggerHelper                                        $loggerHelper,
        private FuseBidGraphqlHelper                        $fuseBidGraphqlHelper,
        NewRelicHeaders                                     $newRelicHeaders,
        private readonly JsonSerializer                     $jsonSerializer,
        array $validations = []
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
     * @inheritdoc
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        try {
            $data = [];
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            foreach ($requests as $request) {
                $args = $request->getArgs();

                $inputArguments = $args[self::INPUT];
                $contactInformation = $requests[0]->getArgs()[self::INPUT][self::CONTACT_INFORMATION];
                $hasFedexAccountNumber = isset($contactInformation[self::FEDEX_ACCOUNT_NUMBER]);
                $hasLteIdentifier = isset($contactInformation[self::LTE_IDENTIFIER]);
                $hasFedexShipAccountNumber = isset($contactInformation[self::FEDEX_SHIP_ACCOUNT_NUMBER]);
                if ($this->fuseBidGraphqlHelper->validateToggleConfig()) {
                    $this->fuseBidGraphqlHelper->updateCartAndCustomerForFuseBid(
                        $inputArguments
                    );
                    $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
                    $this->cart =  $this->fuseBidGraphqlHelper->getCartForBidQuote(
                        $inputArguments[self::CART_ID],
                        $storeId
                    );
                } else {
                    $this->cart = $this->cartModel->getCart($inputArguments[self::CART_ID], $context);
                }
                if ($this->config->isAddOrUpdateFedexAccountNumberEnabled() && ($hasFedexAccountNumber || $hasFedexShipAccountNumber)) {
                    $fedexAccountNumber = $contactInformation[self::FEDEX_ACCOUNT_NUMBER] ?? null;
                    $fedexShipAccountNumber =  $contactInformation[self::FEDEX_SHIP_ACCOUNT_NUMBER] ?? null;
                    $lteIdentifier = null;
                    if ($this->config->isSupportLteIdentifierEnabled() && $hasLteIdentifier) {
                        $lteIdentifier =  $contactInformation[self::LTE_IDENTIFIER];
                    }
                    $this->cart->setLteIdentifier($lteIdentifier);
                    $this->setFedexAccountNumber->setFedexAccountNumber($fedexAccountNumber, $fedexShipAccountNumber, $this->cart);
                    try {
                        $this->fxoRateQuote->getFXORateQuote($this->cart);
                    } catch (GraphQlFujitsuResponseException $e) {
                        $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on getting FXO Rate Quote. ' . $e->getMessage(), $headerArray);
                        throw new GraphQlFujitsuResponseException(__($e->getMessage()));
                    }
                }

                /** if alternate contact exists, it will save the alternate
                 * contact information instead regular contact, based
                 * on app/code/Fedex/Delivery/Controller/Quote/Create.php
                 */
                $alternateContact = $contactInformation[self::ALTERNATE_CONTACT] ?? null;
                $shippingContact = $contactInformation;
                if ((isset($contactInformation[self::ALTERNATE_CONTACT])) && (!empty($alternateContact))) {
                    $shippingContact = $alternateContact;
                }

                $this->request->setParam(self::CONTACT_INFORMATION_KEY, $contactInformation);

                if (key_exists(self::ORGANIZATION_KEY, $contactInformation)) {
                    $shippingContact[self::MAPPED_ORGANIZATION_ATTRIBUTE] = $contactInformation[self::ORGANIZATION_KEY];
                }

                $integration = $this->cartIntegrationRepository->getByQuoteId($this->cart->getId());

                /** Saving customer contact details into quote_address */
                $quoteShip = $this->cart->getShippingAddress();
                $this->setContactInfo($this->cart, $shippingContact, $integration);

                if (!key_exists(self::ORGANIZATION_KEY, $contactInformation)) {
                    $contactInformation[self::ORGANIZATION_KEY] = $quoteShip->getCompany();
                }

                /** Saving customer contact or alternate contact in existing fields on quote table */
                $this->cartModel->setCustomerCartData($this->cart, $shippingContact, $alternateContact);

                $this->saveRetailCustomerId->execute($integration, $contactInformation[self::RETAIL_CUSTOMER_ID]);

                $this->cartRepository->save($this->cart);

                $alternateContactResponse = $alternateContact ?
                    [self::FIRST_NAME => $alternateContact[self::FIRST_NAME],
                        self::LAST_NAME => $alternateContact[self::LAST_NAME],
                        self::EMAIL => $alternateContact[self::EMAIL],
                        self::TELEPHONE => $alternateContact[self::TELEPHONE],
                        self::EXT_KEY => $alternateContact[self::EXT_KEY]
                    ] : null;

                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);

                $data = [
                    self::CART_ID => $inputArguments[self::CART_ID],
                    self::CONTACT_INFORMATION => [
                        self::FIRST_NAME => $contactInformation[self::FIRST_NAME],
                        self::LAST_NAME => $contactInformation[self::LAST_NAME],
                        self::EMAIL => $contactInformation[self::EMAIL],
                        self::TELEPHONE => $contactInformation[self::TELEPHONE],
                        self::EXT_KEY => $contactInformation[self::EXT_KEY],
                        self::FEDEX_ACCOUNT_NUMBER => $fedexAccountNumber ?? null,
                        self::LTE_IDENTIFIER => $lteIdentifier ?? null,
                        self::FEDEX_SHIP_ACCOUNT_NUMBER => $fedexShipAccountNumber ?? null,
                        self::HAS_ALTERNATE_PERSON => (bool)$alternateContact,
                        self::ALTERNATE_CONTACT => $alternateContactResponse
                    ],
                ];

                $data[self::CONTACT_INFORMATION][self::ORGANIZATION_KEY] = $contactInformation[self::ORGANIZATION_KEY] ?? $quoteShip->getCompany();
            }
            $response = $this->batchResponseFactory->create();
            foreach ($requests as $request) {
                $response->addResponse(
                    $request,
                    $data
                );
            }
            return $response;
        } catch (\Exception $e) {
            $this->loggerHelper->error(__CLASS__ . ':' . __METHOD__ . ':' . __LINE__ .
                ' Error on saving contact information into cart. ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
            throw new GraphQlInputException(__('Error on saving contact information into cart'));
        }
    }

    /**
     * @param $cart
     * @param array $shippingContact
     * @param $integration
     * @return void
     */
    public function setContactInfo($cart, array $shippingContact, $integration): void
    {
        $deliveryData = $this->jsonSerializer->unserialize($integration->getDeliveryData() ?? '{}');
        $hasPickupWithoutShipping = $integration->getPickupLocationId() && empty($deliveryData['shipping_method']);
        $isDeliveryDataEmpty = empty($deliveryData);
        if ($hasPickupWithoutShipping || $isDeliveryDataEmpty) {
            foreach ([$cart->getShippingAddress(), $cart->getBillingAddress()] as $address) {
                if (!is_object($address)) {
                    continue;
                }
                $this->setDataInAddress($address, $shippingContact);
            }
        } else {
            $this->setDataInAddress($cart->getBillingAddress(), $shippingContact);
        }
    }

    /**
     * @param $item
     * @param $shippingContact
     * @return void
     */
    public function setDataInAddress($item, $shippingContact): void
    {
        if ($item->getId()) {
            $this->cartModel->setContactInfo($item, $shippingContact);
            if (key_exists(self::MAPPED_ORGANIZATION_ATTRIBUTE, $shippingContact)
            ) {
                $item->setCompany($shippingContact[self::MAPPED_ORGANIZATION_ATTRIBUTE]);
            }
            $item->save();
        }
    }
}
