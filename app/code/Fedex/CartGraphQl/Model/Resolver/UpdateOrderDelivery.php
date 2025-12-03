<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Exception;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataHandler;
use Fedex\CartGraphQl\Model\Resolver\UpdateOrderDelivery\DataProvider;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\NewRelicHeaders;

class UpdateOrderDelivery extends AbstractResolver
{
    const CART_ID = 'cart_id';
    const REQUESTED_PICKUP_LOCAL_TIME = 'requestedPickupLocalTime';

    /**
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationBatchComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param DataProvider $dataProvider
     * @param InstoreConfig $instoreConfig
     * @param FXORateQuote $fxoRateQuote
     * @param DataHandler $dataHandler
     * @param Cart $graphqlCart
     * @param array $validations
     */
    public function __construct(
        RequestCommandFactory $requestCommandFactory,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        ValidationBatchComposite $validationComposite,
        NewRelicHeaders $newRelicHeaders,
        protected DataProvider $dataProvider,
        protected InstoreConfig $instoreConfig,
        protected FXORateQuote $fxoRateQuote,
        protected DataHandler $dataHandler,
        protected Cart $graphqlCart,
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
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     * @throws GraphQlFujitsuResponseException
     */
    public function proceed(ContextInterface $context, Field $field, array $requests, array $headerArray): BatchResponse
    {
        $response = $this->batchResponseFactory->create();
        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);

        foreach ($requests as $request) {
            $args = $request->getArgs();
            $inputArguments = $args['input'];
            try {
                $cart = $this->graphqlCart->getCart($inputArguments[self::CART_ID], $context);
                $this->dataHandler->execute($cart, $inputArguments);
                if (!$this->graphqlCart->checkIfQuoteIsEmpty($cart)) {
                    $rateQuoteResponse = $this->fxoRateQuote->getFXORateQuote($cart);

                    /**
                     * Log RAQ response and verify requestedPickupLocalTime against due_date
                     */
                    if ($this->instoreConfig->isDeliveryDatesFieldsEnabled()) {
                        $logResponse = is_array($rateQuoteResponse) ? $rateQuoteResponse : [];
                        $this->logRequestedPickupLocalTime($logResponse, $inputArguments, $headerArray);
                    }
                }
            } catch (GraphQlFujitsuResponseException|Exception $e) {
                $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' Error on getting FXO Rate Quote. ' . $e->getMessage(), $headerArray);
                $this->loggerHelper->error('GTN: ' . $cart?->getGtn(), $headerArray);
                throw new GraphQlFujitsuResponseException(__($e->getMessage()));
            }

            $this->loggerHelper->info(
                __METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray
            );

            $response->addResponse(
                $request,
                $this->dataProvider->getFormattedData($cart, $rateQuoteResponse ?? [])
            );
        }
        return $response;
    }

    /**
     * @param array $rateQuoteResponse
     * @param array $inputArguments
     * @param array $headerArray
     * @return void
     */
    private function logRequestedPickupLocalTime(array $rateQuoteResponse, array $inputArguments, array $headerArray): void
    {
        $shippingData = $inputArguments['shipping_data'] ?? [];
        $pickupData = $inputArguments['pickup_data'] ?? [];
        $dueDate = $shippingData['due_date'] ?? $pickupData['due_date'] ?? null;

        $rateQuoteDetails = $rateQuoteResponse['output']['rateQuote']['rateQuoteDetails'][0] ?? [];
        $rateQuotePickupTime = $rateQuoteDetails[self::REQUESTED_PICKUP_LOCAL_TIME] ?? null;

        if (!$rateQuotePickupTime || !$dueDate) {
            return;
        }

        if ($dueDate != $rateQuotePickupTime) {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__
                . ' DUE_DATE is different than REQUESTED_PICKUP_LOCAL_TIME'
                . json_encode(array(
                    'DUE_DATE' => $dueDate,
                    self::REQUESTED_PICKUP_LOCAL_TIME => $rateQuotePickupTime
                )),
                $headerArray
            );
            $this->loggerHelper->info(
                __METHOD__ . ':' . __LINE__ . ' RAQ response: ' . json_encode($rateQuoteDetails),
                $headerArray
            );
        }
    }
}
