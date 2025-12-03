<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Fedex\GraphQl\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\CartGraphQl\Model\Note\OrderNotes;
use Fedex\ComputerRental\Model\CRdataModel;

class FXORateQuoteDataArray
{
    public const FEDEXACCOUNTNUMBER = "fedExAccountNumber";
    public const ORDER_CLIENT_MAGENTO = "MAGENTO";
    public const ORDER_CLIENT_FUSE = "FUSE";

    /**
     * @param Data $graphQlHelper
     * @param FXORateQuoteDataArrayContext $context
     * @param CRdataModel $crData
     */
    public function __construct(
        private Data $graphQlHelper,
        private FXORateQuoteDataArrayContext $context,
        private readonly OrderNotes $orderNotes,
        private CRdataModel $crData
    ) {
    }

    /**
     * Get Order Details
     *
     * @param object $rateQuoteDataObject
     * @return array|array[]
     */
    public function getRateQuoteRequest($rateQuoteDataObject, $getInstoreConfigurationNotesChecks = false)
    {
        $quoteObject = $rateQuoteDataObject->getQuoteObject();
        $fedExAccountNumber = $rateQuoteDataObject->getFedExAccountNumber();
        $lteIdentifier = $rateQuoteDataObject->getLteIdentifier();
        $productsData = $rateQuoteDataObject->getProductsData();
        $orderNumber = $rateQuoteDataObject->getOrderNumber();
        $webhookUrl = $rateQuoteDataObject->getWebhookUrl();
        $recipients = $rateQuoteDataObject->getRecipients();
        $promoCodeArray = $rateQuoteDataObject->getPromoCodeArray();
        $site = $rateQuoteDataObject->getSite();
        $siteName = $rateQuoteDataObject->getSiteName();
        $isGraphQlRequest = $rateQuoteDataObject->getIsGraphQlRequest();
        $orderClientData = $isGraphQlRequest === true ? static::ORDER_CLIENT_FUSE : static::ORDER_CLIENT_MAGENTO;
        $objectLocationId = $rateQuoteDataObject->getQuoteLocationId();
        $orderNotes = $rateQuoteDataObject->getOrderNotes();
        $quoteLocationId = !empty($objectLocationId) ? $objectLocationId : null;
        $validateContent = $rateQuoteDataObject->getValidateContent();

        $previousQuoteId = $quoteObject->getData('fjmp_quote_id');
        $fedexLocationId = null;
        if($this->crData->isRetailCustomer() && (!$isGraphQlRequest)){
            $fedexLocationId = $this->crData->getLocationCode();
        }

        $rateQuoteRequestData = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => $quoteLocationId,
                'previousQuoteId' => $previousQuoteId,
                'action' => 'SAVE',
                'retailPrintOrder' => [
                    self::FEDEXACCOUNTNUMBER => $fedExAccountNumber ?: null,
                    'lteIdentifier' => $lteIdentifier ?: null,
                    'origin' => [
                        'orderNumber' => $orderNumber,
                        'orderClient' => $orderClientData,
                        'site' => $site,
                        'siteName' => $siteName,
                        'userReferences' => null,
                        'fedExLocationId'=>$fedexLocationId
                    ],
                    'orderContact' => $this->getOrderContact($quoteObject),
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => $webhookUrl,
                            'auth' => null,
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => $productsData,
                    'recipients' => $this->getRecipientDetail($quoteObject, $recipients),
                    'notes' => $orderNotes ? [['text' => $orderNotes]] : []
                ],
                'coupons' => !empty($promoCodeArray['code']) ? [$promoCodeArray] : null,
                'teamMemberId' => $this->graphQlHelper->getJwtParamByKey('employeeNumber'),
                'validateContent' => $validateContent
            ],
        ];

        $rateQuoteRequestData['rateQuoteRequest']['retailPrintOrder']['notes'] =
            $this->getOrderNotesFormatted($quoteObject, $getInstoreConfigurationNotesChecks);

        return $rateQuoteRequestData;
    }

    /**
     * Get Order Contact
     */
    public function getOrderContact($quote)
    {
        $orderContact = null;
        if (
            $quote->getCustomerFirstname()
            && $quote->getCustomerLastname()
            && $quote->getCustomerEmail()
            && $quote->getCustomerTelephone()
        ) {
            $contactId = null;
            if ($this->context->getRequestQueryValidator()->isGraphQl()) {
                try {
                    $quoteIntegration = $this->context->getCartIntegrationRepositoryInterface()->getByQuoteId($quote->getId());
                    $contactId = $quoteIntegration->getRetailCustomerId();
                } catch (NoSuchEntityException $e) {
                    $contactId = null;
                }
            }
            $quoteShip = $quote->getShippingAddress();

            $orderContact =  [
                'contact' => [
                    'contactId' => $contactId,
                    'personName' => [
                        'firstName' => $quote->getCustomerFirstname(),
                        'lastName' => $quote->getCustomerLastname()
                    ],
                    'company' => [
                        'name' => $this->context->getOrganization()
                            ->getOrganization($quoteShip->getCompany() ?? '')
                    ],
                    'emailDetail' => [
                        'emailAddress' => $quote->getCustomerEmail()
                    ],
                    'phoneNumberDetails' => [
                        0 => [
                            'phoneNumber' => [
                                'number' => $quote->getCustomerTelephone(),
                                'extension' => !empty($quote->getExtNo()) ? $quote->getExtNo() : null
                            ],
                            'usage' => 'PRIMARY'
                        ]
                    ]
                ]
            ];
        }

        return $orderContact;
    }

    /**
     * Get Recipient Detail
     *
     */
    public function getRecipientDetail($quote, $recipients)
    {
        if (!empty($recipients)) {
            $contactDetail = $this->getOrderContact($quote);
            if (!empty($contactDetail)) {
                $recipients[0]['contact'] = $contactDetail['contact'];
            }

            return $recipients;
        }

        return null;
    }

    /**
     * @param $quoteObject
     * @return array|null
     */
    private function getOrderNotesFormatted($quoteObject, $getInstoreConfigurationNotesChecks): ?array
    {
        $orderNotesObjectData = $quoteObject->getData('order_notes');
        if ($getInstoreConfigurationNotesChecks) {
            $orderNotesObjectData = $this->orderNotes->getCurrentNotes($orderNotesObjectData, $quoteObject->getId());
        }
        $orderNotes = !empty($orderNotesObjectData) ? json_decode($orderNotesObjectData) : null;

        if (!empty($orderNotes) && !is_array($orderNotes)) {
            $orderNotes = [$orderNotes];
        }

        return $orderNotes;
    }
}
