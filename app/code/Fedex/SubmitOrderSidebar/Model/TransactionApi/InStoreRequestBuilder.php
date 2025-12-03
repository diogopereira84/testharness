<?php
/**
 * @category     Fedex
 * @package      Fedex_SubmitOrderSidebar
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model\TransactionApi;

use Fedex\GraphQl\Helper\Data;

class InStoreRequestBuilder extends AbstractRequestBuilder
{
    public const COMPANY_NAME = 'FXO';
    public const USAGE = 'PRIMARY';
    public const DELIVERY_LINES = 'deliveryLines';
    public const EMAIL_ADDRESS_TEXT = 'emailAddress';
    public const PHONE_NUMBER_DETAILS = 'phoneNumberDetails';
    public const PHONE_NUMBER_TEXT = 'phoneNumber';
    public const NUMBER_TEXT = 'number';
    public const OUTPUT = 'output';
    public const CHECKOUT = 'checkout';
    public const LINE_ITEMS = 'lineItems';
    public const RETAIL_PRINT_ORDER = 'retailPrintOrderDetails';
    public const ORDER_LINE_DETAILS = 'orderLineDetails';
    public const TENDERS = 'tenders';
    public const TRANSACTION_TOTALS = 'transactionTotals';

    /**
     * @param Data $graphQlHelper
     */
    public function __construct(
        protected Data $graphQlHelper
    )
    {
    }

    public function build($fjmpRateQuoteId): array
    {
        return [
            'checkoutRequest' => [
                'transactionHeader' => [
                    'requestDateTime' => $this->getDateFormatted(),
                    'rateQuoteId' => $fjmpRateQuoteId,
                    'teamMemberId' => $this->graphQlHelper->getJwtParamByKey('employeeNumber'),
                    'type' => 'ORDER'
                ],
                'orderClient' => 'FUSE'
            ],
        ];
    }

    /**
     * Prepare get transaction response in case of transaction api timeout
     *
     * @param object $quote
     * @param array $transactionResponseData
     * @return array
     */
    public function prepareGetTransactionResponse($quote, $transactionResponseData)
    {
        $prepareData = [];
        $prepareData['transactionId'] = $transactionResponseData['transactionId'];
        $checkout = $transactionResponseData[self::OUTPUT]['transaction'];
        $prepareData[self::OUTPUT][self::CHECKOUT]['transactionHeader'] = $checkout['transactionHeader'];

        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['productLines'] = $checkout['productLines'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        [self::DELIVERY_LINES] = $checkout[self::DELIVERY_LINES];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderTotalDiscountAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderTotalDiscountAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderGrossAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderGrossAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderNonTaxableAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderNonTaxableAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderTaxExemptableAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderTaxExemptableAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderNetAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderNetAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderTaxableAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderTaxableAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderTaxAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderTaxAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['orderTotalAmount'] = $checkout[self::ORDER_LINE_DETAILS][0]['orderTotalAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::LINE_ITEMS][0][self::RETAIL_PRINT_ORDER][0]
        ['origin'] = $checkout[self::ORDER_LINE_DETAILS][0]['origin'];

        $prepareData[self::OUTPUT][self::CHECKOUT]['contact'] = $this->prepareContactDetails($quote);
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TENDERS] = $checkout[self::TENDERS];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS]['currency'] =
        $checkout[self::TENDERS][0]['currency'] ?? $checkout['currency'] ?? 'USD';
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS]['grossAmount'] = $checkout['grossAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS]['totalDiscountAmount'] =
        $checkout['totalDiscountAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS]['netAmount'] = $checkout['netAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS]['taxAmount'] = $checkout['taxAmount'];
        $prepareData[self::OUTPUT][self::CHECKOUT][self::TRANSACTION_TOTALS]['totalAmount'] = $checkout['totalAmount'];

        return $prepareData;
    }

    /**
     * Prepare contact details in case of transaction api timeout
     *
     * @param object $quote
     * @return array
     */
    public function prepareContactDetails($quote)
    {
        $prepareContact = [];
        $prepareContact['personName']['firstName'] = $quote->getData('customer_firstname');
        $prepareContact['personName']['lastName'] = $quote->getData('customer_lastname');
        $prepareContact['company']['name'] = self::COMPANY_NAME;
        $prepareContact['emailDetail'][self::EMAIL_ADDRESS_TEXT] = $quote->getData('customer_email');
        $prepareContact[self::PHONE_NUMBER_DETAILS][0][self::PHONE_NUMBER_TEXT][self::NUMBER_TEXT] =
        $quote->getData('customer_telephone');
        $prepareContact[self::PHONE_NUMBER_DETAILS][0]['usage'] = self::USAGE;

        return $prepareContact;
    }
}
