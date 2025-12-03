<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Exception;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\Registry;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order as OrderModel;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Fedex\SubmitOrderSidebar\Helper\SubmitOrderOptimizedHelper;
use Fedex\SubmitOrderSidebar\Model\TransactionApi\RateQuoteAndTransactionApiHandler;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\Shipment\Helper\StatusOption as ShipmentHelper;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\CoreApi\Model\LogHelperApi;

/**
 * SubmitOrderApi Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SubmitOrderApi
{
    /**
     * constant for address classification home
     */
    public const HOME = "HOME";

    /**
     * constant for address classification business
     */
    public const BUSINESS = "BUSINESS";

    /**
     * constant for failure
     */
    public const FAILURE ="Failure";

    /**
     * constant for success
     */
    public const SUCCESS = "Success";
    public const QUOTE_ID = ' Quote ID:';
    public const GTN_NUMBER_TEXT = ' GTN Number => ';
    public const ERROR = 'error';
    public const RESPONSE = 'response';
    public const IS_CARD_AUTHORIZE = 'iscardAuthorize';
    public const MSG = 'msg';
    public const RATE_QUOTE_RESPONSE = 'rateQuoteResponse';
    public const OUTPUT = 'output';
    public const RATE_QUOTE_DETAILS = 'rateQuoteDetails';
    public const RATE_QUOTE = 'rateQuote';
    public const TRANSACTION_BY_IDS = 'transactionsByIds';

    /**
     * SubmitOrderApi constructor
     *
     * @param SubmitOrderHelper $submitOrderHelper
     * @param SubmitOrderOptimizedHelper $submitOrderOptimizedHelper
     * @param LoggerInterface $logger
     * @param OrderModel $orderModel
     * @param Registry $registry
     * @param RateQuoteAndTransactionApiHandler $apiHandler
     * @param CheckoutSession $checkoutSession
     * @param ToggleConfig $toggleConfig
     * @param RequestQueryValidator $requestQueryValidator
     * @param InstoreConfig $instoreConfig
     * @param ShipmentHelper $shipmentHelper
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param MarketPlaceHelper $marketPlaceHelper
     * @param CheckoutHelper $checkoutHelper
     * @param LogHelperApi $logHelperApi
     */
    public function __construct(
        protected SubmitOrderHelper $submitOrderHelper,
        protected SubmitOrderOptimizedHelper $submitOrderOptimizedHelper,
        protected LoggerInterface $logger,
        protected OrderModel $orderModel,
        protected Registry $registry,
        protected RateQuoteAndTransactionApiHandler $apiHandler,
        protected CheckoutSession $checkoutSession,
        protected ToggleConfig $toggleConfig,
        private RequestQueryValidator $requestQueryValidator,
        private InstoreConfig $instoreConfig,
        private ShipmentHelper $shipmentHelper,
        private OrderApprovalViewModel $orderApprovalViewModel,
        private MarketPlaceHelper $marketPlaceHelper,
        private CheckoutHelper $checkoutHelper,
        private readonly LogHelperApi $logHelperApi
    )
    {
    }

    /**
     * Unset orderinprogress from session
     *
     * @return mixed
     */
    public function unsetOrderInProgress()
    {
        return $this->submitOrderOptimizedHelper->unsetOrderInProgress();
    }

    /**
     * Is Alternate Contact
     *
     * @return bool|null
     */
    public function isAlternateContact()
    {
        return $this->submitOrderOptimizedHelper->isAlternateContact() ?? null;
    }

    /**
     * Manage Alternate Flags
     * @params boolean $isAlternate|$isAlternatePickup
     */
    public function manageAlternateFlags($isAlternate, $isAlternatePickup)
    {
        $alternateFlag = '';
        if ($isAlternate || $isAlternatePickup) {
            $alternateFlag = true;
        }
        if ($alternateFlag) {
            $this->submitOrderOptimizedHelper->setAlternateContactFlag($alternateFlag);
        }
    }

    /**
     * Is Alternate Pickup Person
     *
     * @return bool|null
     */
    public function isAlternatePickupPerson()
    {
        return $this->submitOrderOptimizedHelper->isAlternatePickupPerson() ?? null;
    }

    /**
     * Call Fujitsu Rate Quote API
     *
     * @param Object $dataObjectForFujistu
     * @return array
     * @throws NoSuchEntityException
     */
    public function callFujitsuRateQuoteApi($dataObjectForFujistu): array
    {
        $data = $dataObjectForFujistu->getOrderData();
        $quoteId = $dataObjectForFujistu->getQuoteId();
        $orderNumber = $dataObjectForFujistu->getOrderNumber();

        /* Code to for duplicate order number */
        $duplicateOrder = $this->submitOrderHelper->isDuplicateOrder($quoteId);
        if ($duplicateOrder) {
            return [self::ERROR => 2, self::MSG => 'Duplicate Order Number', self::RESPONSE => ''];
        }

        $dataString = json_encode($data);
        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': ' . 'Before Fujitsu API call '. self::QUOTE_ID .
            $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' $dataString => ' . $dataString);

        /**  B-1109907: Optimize Configurations  */
        $output = $this->apiHandler->callCurlPost($dataString, 'rate');

        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': ' . 'After Fujitsu API call '.self::QUOTE_ID .
            $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' $output => ' . $output);

        $rateQuoteResponse = json_decode((string)$output, true);

        if (!empty($rateQuoteResponse)) {
            return $this->apiHandler->getCheckoutResponseData($dataObjectForFujistu, $rateQuoteResponse);
        } else {
            $this->unsetOrderInProgress();
            $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': ' . ' Fujitsu rate quote API Failed.');

            return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => ''];
        }
    }

    /**
     * Call api transaction request
     *
     * @param object $dataObjectForFujistu
     * @return array
     * @throws GraphQlFujitsuResponseException
     */
    public function callTransactionAPIRequest($dataObjectForFujistu)
    {
        $quote = $dataObjectForFujistu->getQuoteData();
        $fjmpRateQuoteId = $quote->getData('fjmp_quote_id');
        $rateQuoteResponse = $dataObjectForFujistu->getRateQuoteResponse();
        $orderNumber = $dataObjectForFujistu->getOrderNumber();
        $quoteId = $dataObjectForFujistu->getQuoteId();
        $paymentData = $dataObjectForFujistu->getPaymentData();
        $response = [];

        if ($paymentData->paymentMethod == 'instore') {
            $checkoutResponse = $this->apiHandler->handleInstoreTransactionAPI(
                $fjmpRateQuoteId,
                $quoteId,
                $rateQuoteResponse,
                $dataObjectForFujistu
            );
        } else {
            $checkoutResponse = $this->apiHandler->constructTransactionAPI(
                $quote,
                $fjmpRateQuoteId,
                $rateQuoteResponse,
                $dataObjectForFujistu
            );
        }

        if (!empty($checkoutResponse)) {
            $boolCardAuthorizationStatus = true;
            $retailTransactionId = null;

            if (isset($checkoutResponse[self::RESPONSE])) {
                $checkoutResponseData = json_decode((string)$checkoutResponse[self::RESPONSE]);
                $isWarningAlertToggle = $this->toggleConfig->getToggleConfigValue('explorers_warning_msg_fix');
                $nonWarningAlerts = [];
                //D-97873 added !empty condition (failed tests in UT)
                if (($isWarningAlertToggle && (!empty($checkoutResponseData->errors)
                    || (isset($checkoutResponseData->output->alerts)
                        && is_array($checkoutResponseData->output->alerts)
                        && !empty($checkoutResponseData->output->alerts))))
                ) {
                    $freedomPayMessaging = $this->toggleConfig->getToggleConfigValue('tech_titans_b_2179775');

                    if($freedomPayMessaging) {
                        if(property_exists($checkoutResponseData, 'output')){
                            $nonWarningAlerts = array_filter($checkoutResponseData->output->alerts, function ($alert) {
                                return isset($alert->alertType) && $alert->alertType !== 'WARNING';
                            });

                            if (!empty($nonWarningAlerts)) {
                                $boolCardAuthorizationStatus = false;

                                $this->logger->info(
                                    __METHOD__ . ':' . __LINE__ . ': ' . self::QUOTE_ID .
                                    $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' return => ' .
                                    json_encode([
                                        self::ERROR => 1,
                                        self::MSG => 'Failure',
                                        self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                                        self::RESPONSE => $checkoutResponse,
                                    ])
                                );

                                return [
                                    self::ERROR => 1,
                                    self::MSG => self::FAILURE,
                                    self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                                    self::RESPONSE => $checkoutResponse,
                                ];
                            }
                        }
                    } else {
                        $nonWarningAlerts = array_filter($checkoutResponseData->output->alerts, function ($alert) {
                            return isset($alert->alertType) && $alert->alertType !== 'WARNING';
                        });

                        if (!empty($nonWarningAlerts)) {
                            $boolCardAuthorizationStatus = false;

                            $this->logger->info(
                                __METHOD__ . ':' . __LINE__ . ': ' . self::QUOTE_ID .
                                $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' return => ' .
                                json_encode([
                                    self::ERROR => 1,
                                    self::MSG => 'Failure',
                                    self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                                    self::RESPONSE => $checkoutResponse,
                                ])
                            );

                            return [
                                self::ERROR => 1,
                                self::MSG => self::FAILURE,
                                self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                                self::RESPONSE => $checkoutResponse,
                            ];
                        }
                    }
                } elseif ((!empty($checkoutResponseData->errors)) || (isset($checkoutResponseData->output->alerts)
                    && is_array($checkoutResponseData->output->alerts) && !empty($checkoutResponseData->output->alerts))
                ) {
                    $boolCardAuthorizationStatus = false;

                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__. ': ' . self::QUOTE_ID .
                        $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' return => ' .
                        json_encode([
                            self::ERROR => 1,
                            self::MSG => 'Failure',
                            self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                            self::RESPONSE => $checkoutResponse,
                        ])
                    );

                    return [
                        self::ERROR => 1,
                        self::MSG => self::FAILURE,
                        self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                        self::RESPONSE => $checkoutResponse,
                    ];
                }

                $transactionData = $this->apiHandler->getTransactionIdAndProductLinesAttributes($checkoutResponseData);
                $retailTransactionId = $transactionData['retailTransactionId'];
            }

            if (($checkoutResponse[self::ERROR] == 0)
            && isset($checkoutResponse[self::RESPONSE])
            && $boolCardAuthorizationStatus
            ) {
                $this->logger->info(__METHOD__ . ':' . __LINE__. ': ' . " Retail Transaction Id received :" .
                    $retailTransactionId . " for the quote id " . $quoteId);

                $response = [$checkoutResponse[self::RESPONSE], self::RATE_QUOTE_RESPONSE => $rateQuoteResponse];
            } else {
                $this->unsetOrderInProgress();
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__. ': ' . self::QUOTE_ID . $quoteId . ' Transaction CXS API failed'
                );
                $response = [
                    self::ERROR => 1,
                    self::MSG => self::FAILURE,
                    self::IS_CARD_AUTHORIZE => $boolCardAuthorizationStatus,
                    self::RESPONSE => $checkoutResponse,
                ];
            }
        } else {
            $this->unsetOrderInProgress();
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ': '
                . self::QUOTE_ID . $quoteId . ' $checkoutResponse => Error found no data'
            );
            $response = [self::ERROR => 1, self::MSG => 'Error found no data', self::RESPONSE => ''];
        }

        return $response;
    }

    /**
     * Call Fujitsu Rate Quote API with Save Action before Commit
     *
     * @param object $dataObjectForFujistu
     * @return array
     */
    public function callRateQuoteApiWithSave($dataObjectForFujistu): array
    {
        $data = $dataObjectForFujistu->getOrderData();
        $quote = $dataObjectForFujistu->getQuoteData();
        $quoteId = $quote->getId();
        $orderNumber = $quote->getGtn();
        $dataString = json_encode($data);

        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__ .
            ': Fujitsu SAVE Rate Quote Request before Commit for the ' . self::QUOTE_ID .
            $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' $dataString => ' . $dataString);

        $output = $this->apiHandler->callCurlPost($dataString, 'rate');
        $rateQuoteResponse = json_decode((string)$output, true);
        if (!empty($rateQuoteResponse)) {
            if (empty($rateQuoteResponse['errors']) && isset($rateQuoteResponse[self::OUTPUT])) {
                $this->logHelperApi->info(__METHOD__ . ':' . __LINE__ .
                    ': After Fujitsu API call with Save before Commit ' . self::QUOTE_ID .
                    $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' $output => ' . $output);
            } else {
                return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => $rateQuoteResponse];
            }

            return [self::ERROR => 0, self::MSG => self::SUCCESS, self::RESPONSE => $rateQuoteResponse];
        } else {
            $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': Fujitsu rate quote API Failed before Commit.');
            return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => ''];
        }
    }

    /**
     * Call Fujitsu Rate Quote API
     *
     * @param object $dataObjectForFujistu
     * @return array
     */
    public function callRateQuoteApi($dataObjectForFujistu): array
    {
        $data = $dataObjectForFujistu->getOrderData();
        $quote = $dataObjectForFujistu->getQuoteData();
        $quoteId = $quote->getId();
        $orderNumber = $quote->getGtn();
        $dataString = json_encode($data);

        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': ' . 'Fujitsu Rate Quote API Request '. self::QUOTE_ID .
            $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' $dataString => ' . $dataString);

        $output = $this->apiHandler->callCurlPost($dataString, 'rate');

        $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': ' . 'After Fujitsu API call '. self::QUOTE_ID .
            $quoteId . self::GTN_NUMBER_TEXT . $orderNumber . ' $output => ' . $output);

        $rateQuoteResponse = json_decode((string)$output, true);

        if (!empty($rateQuoteResponse)) {
            if (empty($rateQuoteResponse['errors']) && isset($rateQuoteResponse[self::OUTPUT])) {
                $this->logHelperApi->info(
                    __METHOD__ . ':' . __LINE__. ': ' . " Fujitsu Rate Quote API success for the ". self::QUOTE_ID
                    . $quoteId . self::GTN_NUMBER_TEXT . $orderNumber
                );

                return $this->validateRateQuoteResponse($quote, $rateQuoteResponse, $dataObjectForFujistu->getEproOrder());
            } else {
                $this->logHelperApi->info(__METHOD__ . ':' . __LINE__. ': ' .
                    " Fujitsu Rate Quote API failed for the ". self::QUOTE_ID . $quoteId);

                if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                    $errorMessage = implode(",", array_column($rateQuoteResponse['errors'], 'message'));
                    throw new GraphQlFujitsuResponseException(__("Rate Quote Response Error: " . $errorMessage));
                }

                return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => $rateQuoteResponse];
            }
        } else {
            if ($this->instoreConfig->isEnabledThrowExceptionOnGraphqlRequests()) {
                $message = __("Rate Quote Unexpected Response: " . $output);
                $this->logHelperApi->info('Request: '.$dataString.' Response: '.$output);

                throw new GraphQlFujitsuResponseException($message);
            }

            $this->logHelperApi->info(
                __METHOD__ . ':' . __LINE__. ': ' . ' Fujitsu rate quote API Failed.'
            );

            return [self::ERROR => 1, self::MSG => self::FAILURE, self::RESPONSE => ''];
        }
    }

    /**
     * Validate Rate Quote API Response
     *
     * @param object $quote
     * @param array $rateQuoteResponse
     * @return array
     */
    public function validateRateQuoteResponse($quote, $rateQuoteResponse, $isEpro = null)
    {
        $quoteId = $quote->getId();
        $orderNumber = $quote->getGtn();
        $fjmpRateQuoteId = $this->getRateQuoteId($rateQuoteResponse);
        $quote = $this->submitOrderHelper->getActiveQuote($quoteId);
        if (!$isEpro) {
            $isSetOrderId = $this->submitOrderHelper->isSetOrderId($quote, $orderNumber);

            if (!$isSetOrderId) {
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ . ': ' . 'Reserved order id was not updated for quote Id:' . $quoteId
                );

                return [self::ERROR => 1, self::MSG => 'Reserved order id was not updated for quote Id:' . $quoteId];
            }
        }

        $this->logger->info(
            __METHOD__ . ':' . __LINE__. ': ' . " Fujitsu Rate ". self::QUOTE_ID .
            $fjmpRateQuoteId . " for the ". self::QUOTE_ID . $quoteId . self::GTN_NUMBER_TEXT . $orderNumber
        );

        return [self::ERROR => 0, self::MSG => self::SUCCESS, self::RESPONSE => $rateQuoteResponse];
    }

    /**
     * Create Order before Payment
     *
     * @param object $quote
     * @param string $paymentData
     * @param object $dataObjectForFujistu
     * @return object
     */
    public function createOrderBeforePayment($paymentData, $dataObjectForFujistu)
    {
        $quote = $dataObjectForFujistu->getQuoteData();
        if ($this->orderApprovalViewModel->isOrderApprovalB2bEnabled()) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ': '
                .': Start of order creation with Pending Approval status for '. self::QUOTE_ID . $quote->getId()
            );
        } else {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ': '
                .': Start of order creation with pending status for '. self::QUOTE_ID . $quote->getId()
            );
        }

        $paymentInfo = $this->submitOrderHelper->getPaymentParametersData($quote, $paymentData);
        $quote = $this->submitOrderHelper->setQuotePaymentInfo($quote, $paymentInfo);
        $this->checkoutSession->setRateQuoteResponse($dataObjectForFujistu->getRateQuoteResponse());

        $this->logger->info(__METHOD__ . ':' . __LINE__. ': ' . ': Finished check payment info '. self::QUOTE_ID
        . $quote->getId() . ' $paymentInfo["paymentMethod"] =>' . $paymentInfo['paymentMethod']);

        if ((!$this->apiHandler->getCommercialCustomer()) && ($this->apiHandler->getCustomer())) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates()
            ->setShippingMethod($shippingAddress->getShippingMethod());
        }

        $quote = $this->submitOrderHelper->updateCustomerInformation($quote);
        $this->logger->info(__METHOD__ . ':' . __LINE__. ': ' . ' Before Verifying Quote Integrity:' . $quote->getId());
        $this->submitOrderHelper->verifyQuoteIntegrity($quote);
        $this->logger->info(__METHOD__ . ':' . __LINE__. ': ' .' After Verifying Quote Integrity:' . $quote->getId());

        $this->logger->info(__METHOD__ . ':' . __LINE__. ': ' . ' Before Save quote '. self::QUOTE_ID
            . $quote->getId() . ' $paymentInfo["paymentMethod"] =>' . $paymentInfo['paymentMethod']);

        $rateQuoteResponse = $dataObjectForFujistu->getRateQuoteResponse();
        $productLines = '';
        $rateQuoteDetails = [];
        $estimatedVsActual = '';

        if (!empty($rateQuoteResponse) && isset($rateQuoteResponse[self::OUTPUT])) {
            $productLines = $this->submitOrderOptimizedHelper->getProductLinesDetails($rateQuoteResponse);
            $rateQuoteDetails = $rateQuoteResponse[self::OUTPUT][self::RATE_QUOTE][self::RATE_QUOTE_DETAILS];
            $estimatedVsActual = $this->submitOrderOptimizedHelper->getEstimatedVsActualDetails($rateQuoteResponse);
        }

        $quote->setData('estimated_vs_actual', $estimatedVsActual);
        try {
            $quote->save();
        } catch (\Exception $e) {
            $this->logger->critical('Exception occurred while saving quote for the Quote Id : '. $quote->getId()
                .':'. $e->getMessage());
        }

        // Create Order With Pending Status From Quote
        $order = $this->submitOrderHelper->createOrderFromQuote($quote);

        $quoteObject = $this->submitOrderOptimizedHelper->getCheckoutSessionQuote();
        $this->updateQuoteStatusAndTimeoutFlag($quoteObject, true, 0);

        // Store data in sales_order_payment table with pending order
        $orderPaymentObject = $order->getPayment();
        $orderPaymentObject->setFedexAccountNumber($paymentInfo['fedexAccountNumber']);
        $orderPaymentObject->setPoNumber($paymentInfo['fedexPoNumber']);
        $orderPaymentObject->setCcOwner($paymentInfo['ccOwner']);
        $orderPaymentObject->setCcLast4($paymentInfo['ccNumber']);
        $orderPaymentObject->setProductLineDetails(json_encode($productLines));
        if ($paymentInfo['useSitePayment']) {
            $orderPaymentObject->setSiteConfiguredPaymentUsed(1);
        }

        try {
            $order->save();
        } catch (\Exception $e) {
            $this->logger->critical('Exception occurred while updating payment detail with Order Id : '. $order->getId()
                .':'. $e->getMessage());
        }

        // Save Producing Address for shipment
        $this->submitOrderOptimizedHelper->producingAddress($rateQuoteDetails, $order);

        return $order;
    }

    /**
     * Update order payment after get rate quote response
     *
     * @param object $dataObjectForFujistu
     * @param object $order
     * @return array $checkoutResponse
     */
    public function updateOrderAfterPayment($dataObjectForFujistu, $order)
    {
        $checkoutResponse = $this->callTransactionAPIRequest($dataObjectForFujistu);

        if (empty($checkoutResponse[self::ERROR])) {
            $retailTransactionId = $this->submitOrderOptimizedHelper->getRetailTransactionId($checkoutResponse);
            $this->saveOrderWithNewStatus($order, $retailTransactionId, $dataObjectForFujistu->getQuoteData());
            if (($this->toggleConfig->getToggleConfigValue('mazegeeks_B_2599706')) &&
                ($this->submitOrderHelper->isEproQuote($order->getQuoteId()))
            ) {
                $this->submitOrderHelper->prepareOrderProducingAddress(
                    $checkoutResponse[0],
                    $order->getId()
                );
            }
        } elseif ($checkoutResponse[self::RESPONSE][self::MSG] == 'timeout') {
            $this->logger->info(__METHOD__ . ':' . __LINE__. ': ' .
                ' Internal server timedout after transaction call for GTN Number:' .$order->getIncrementId());
            $quote = $this->submitOrderOptimizedHelper->getCheckoutSessionQuote();
            $this->setTimeoutFlag($quote);
            $checkoutResponse = [
                self::ERROR => 1,
                self::MSG => 'timeout',
                self::RESPONSE => 'Internal Server Timeout'
            ];
        }

        return $checkoutResponse;
    }

    /**
     * Delete Order if status pending
     *
     * @param int|string $gtnNumber
     */
    public function deleteOrderWithPendingStatus($gtnNumber)
    {
        $orderObj = $this->orderModel->loadByIncrementId($gtnNumber);
        // Check if order already created with same GTN number with pending status
        if (isset($orderObj) && $orderObj->getStatus() == "pending") {
            try {
                // Pending status order delete from database
                $this->registry->register('isSecureArea', 'true');
                $this->orderModel->delete();
                $this->registry->unregister('isSecureArea');
                $this->logger->info(__METHOD__ . ':' . __LINE__ .
                    ' : Pending status order was deleted for the GTN:'. $gtnNumber);
            } catch (LocalizedException $e) {
                $this->logger->error(
                    __METHOD__ . ':' . __LINE__. ': '. ' Pending status order was not deleted for GTN: '
                    .$gtnNumber. ' Error'. $e->getMessage()
                );
            } catch (\Exception $e) {
                $this->logger->critical('Exception occurred while deleting pending status order: '. $e->getMessage());
            }
        }
    }

    /**
     * Set timeout flag
     *
     * @param object $quote
     * @return $quote
     */
    public function setTimeoutFlag($quote)
    {
        $this->logger->info(
            __METHOD__ . ':' . __LINE__. ': ' . ' Set timeout flag code 1 for '. self::QUOTE_ID .$quote->getId()
        );

        try {
            $quote->setIsTimeout(1)->save();
        } catch (Exception $e) {
            $this->logger->critical(
                'Exception occurred while set timeout flag code 1 for the Quote Id : '. $quote->getId()
                .':'. $e->getMessage()
            );
        }

        return $quote;
    }

    /**
     * Get Transaction Response in case of timeout alert
     *
     * @param object $quote
     * @param string $transactionId
     * @param bool $orderConfirmationPage
     * @return array
     */
    public function getTransactionAPIResponse($quote, $transactionId = null, $orderConfirmationPage = false)
    {
        $transactionApiResponseData = [];
        if ($quote->getIsTimeout()) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__ .' Transaction was timed-out for the GTN Number => '. $quote->getGtn()
            );
            $retailTransactionId = $this->getRetailOrderTransactionId($quote, $transactionId);
            if (!empty($retailTransactionId)) {
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__ .' Retail Transaction Id => '.$retailTransactionId
                    .' in case of transaction timeout for the GTN Number => '. $quote->getGtn()
                );
                $transactionApiResponse = $this->apiHandler->getTransactionResponse($quote, $retailTransactionId);
                if (empty($transactionApiResponse[self::ERROR])) {
                    $order = $this->orderModel->loadByIncrementId($quote->getGtn());
                    $this->saveOrderWithNewStatus($order, $retailTransactionId, $quote);

                    $this->setMiraklOrderInformation($transactionApiResponse, $order);
                    if ($orderConfirmationPage) {
                        $transactionApiResponseData = [
                            '0' => json_encode($transactionApiResponse[self::RESPONSE]),
                            self::RATE_QUOTE_RESPONSE => $transactionApiResponse[self::RATE_QUOTE_RESPONSE]
                        ];
                    } else {
                        $transactionApiResponseData = [$transactionApiResponse];
                    }
                }
            }
            $this->submitOrderHelper->unsetRetailTransactionId();
        }

        return $transactionApiResponseData;
    }

    protected function setMiraklOrderInformation(&$transactionApiResponse, $order)
    {
        $items = $order->getItemsCollection();
        $miraklItems =  $additionalDataArray = [];
        foreach ($items as $item) {
            if ($item->getMiraklOfferId()) {
                $additionalData = $item->getAdditionalData();
                $additionalDataArray = (array)json_decode($additionalData);
                $miraklItems[] = [
                    'name' => ($additionalDataArray['marketplace_name'] ?? $additionalDataArray['navitor_name']),
                    'preview_url' => $additionalDataArray['image'],
                    'price' => $this->checkoutHelper->formatPrice($additionalDataArray['unit_price']),
                    'subtotal' => $this->checkoutHelper->convertPrice($additionalDataArray['total'], true),
                    'features' => $additionalDataArray['features'] ?? [],
                    'quoteItemId' => $item->getQuoteItemId(),
                    'additional_data' => $additionalData,
                    'qty' => $additionalDataArray['quantity']
                ];
            }
        }
        $marketPlaceInfo = [
            'shipping_method' => $additionalDataArray['mirakl_shipping_data']->method_title ?? '',
            'shipping_price' => $additionalDataArray['mirakl_shipping_data']->amount ?? '',
            'lineItems' => $miraklItems,
            'shipping_address' => $additionalDataArray['mirakl_shipping_data']->address ?? []
        ];

        $transactionApiResponse['response']['output']['checkout']['marketPlace'] = $marketPlaceInfo;
    }

    /**
     * Get Retail Order Transaction Id
     *
     * @param object $quote
     * @param string|null $retailTransactionId
     * @return string
     */
    public function getRetailOrderTransactionId($quote, $retailTransactionId)
    {
        if (!empty($retailTransactionId)) {
            return $retailTransactionId;
        } elseif ($this->submitOrderHelper->getRetailTransactionIdFromSession()) {
            return $this->submitOrderHelper->getRetailTransactionIdFromSession();
        } else {
            return $this->getRetailTransactionIdByGtnNumber($quote->getGtn());
        }
    }

    /**
     * Get Retail Transaction Id by GTN Number
     *
     * @param int|string $gtnNumber
     * @return string
     */
    public function getRetailTransactionIdByGtnNumber($gtnNumber)
    {
        $retailTransactionId = '';

        $lookupsRequest = [
            "transactionsByIdsRequest" => [
                [
                    "type" => "PRINT_ORDER_NUMBER",
                    "value" => $gtnNumber
                ]
            ]
        ];

        $dataString = json_encode($lookupsRequest);
        $this->logger->info(__METHOD__ . ':' . __LINE__ .' '.
            'Transaction Search API Request => '. $dataString.' for'. self::GTN_NUMBER_TEXT . $gtnNumber);

        $output = $this->apiHandler->callCurlPost($dataString, 'transaction_search');

        $this->logger->info(__METHOD__ . ':' . __LINE__ .' '.
            'Transaction Search API Response => '. $output.' for'. self::GTN_NUMBER_TEXT . $gtnNumber);

        $transactionSearchApiData = json_decode((string)$output, true);

        if (!empty($transactionSearchApiData)
        && isset($transactionSearchApiData[self::OUTPUT])
        && isset($transactionSearchApiData[self::OUTPUT][self::TRANSACTION_BY_IDS])
        && !empty($transactionSearchApiData[self::OUTPUT][self::TRANSACTION_BY_IDS][0]['transactionExist'])
        ) {
            $retailTransactionId =
            $transactionSearchApiData[self::OUTPUT][self::TRANSACTION_BY_IDS][0]['transactionInfo'][0]['id'];
        }

        return $retailTransactionId;
    }

    /**
     * Order status update after transaction successfully completed
     *
     * @param object $order
     * @param int|string $retailTransactionId
     * @param object $quote
     */
    public function saveOrderWithNewStatus($order, $retailTransactionId, $quote)
    {
        $isAlternatePickupPerson = $quote->getIsAlternatePickup() ? true : false;
        $isEproQuote = $quote->getIsEproQuote() ? true : false;
        $isAlternatePickupEmailToggle = $this->toggleConfig->getToggleConfigValue('explorers_order_email_alternate_pick_up_person');
        if ($isAlternatePickupEmailToggle && $isAlternatePickupPerson && $isEproQuote) {
            $order->getShippingAddress()->setEmail($quote->getShippingAddress()->getEmail());
        }
        if ($this->toggleConfig->getToggleConfigValue('b_2088132_toggle_po_number_email')) {
            $poNumber = '';
            if ($quote->getBillingFields()) {
                $quoteBillingFields = json_decode($quote->getBillingFields(), true);
                $firstFieldIsset = isset($quoteBillingFields['items'][0]['first_field']);
                $d195387 = $this->toggleConfig->getToggleConfigValue('tiger_d195387');
                if (isset($quoteBillingFields['totalRecords']) && isset($quoteBillingFields['items'])) {
                    if (isset($quoteBillingFields['items'][0]) && ($firstFieldIsset || !$d195387)) {
                        //B-2088132 check billing field to get first reference value as PO NUMBER
                        $poNumber = $quoteBillingFields['items'][0]['value'];
                    }
                }
            }
        }
        $this->updateQuoteStatusAndTimeoutFlag($quote, false, 0);
        try {
            $orderPaymentObject = $order->getPayment();
            $orderPaymentObject->setRetailTransactionId($retailTransactionId);
            if (!empty($poNumber) && // check if poNumber from billingFields is not blank
                $orderPaymentObject->getPoNumber() == "" && // check if poNumber is blank in sales_order_payment table
                $this->toggleConfig->getToggleConfigValue('b_2088132_toggle_po_number_email') // Toggle check
            ) {
                $orderPaymentObject->setPoNumber($poNumber);
            }
            $orderPaymentObject->save();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
            .': Error while saving transaction Id in payment table for the order: ' .$order->getId().
             ' is: '. $e->getMessage());
        }
        try {
            $order->setBillingFields($quote->getBillingFields());
            $order->setStatus("new");
            $order->save();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
            .': Error while updating order status to new for the order id: ' .$order->getId().
             ' is: '. $e->getMessage());
        }

        $this->logger->info(
            __METHOD__ . ':' . __LINE__ .' Order updated with new status for the Order Id => '. $order->getId()
        );

        $this->submitOrderHelper->setCookie('quoteId', $order->getQuoteId());

        // If Order id is generated then call reorderable Instance API to preserve the instance.
        $this->submitOrderHelper->reorderInstanceSave($order->getId());

        // Push quote id in queue to clean item instance from quote
        $this->submitOrderHelper->cleanProductItemInstance($order->getQuoteId());

        // Clear quote and generate new one
        $this->submitOrderHelper->clearQuoteCheckoutSessionAndStorage($order->getQuoteId(), $order->getId());
    }

    /**
     * Order status update when OMS sent status update (Call Sequence workflow)
     *
     * @param object $order
     * @return bool
     */
    public function updateOrderWithNewStatus($order)
    {
        $quote = $this->submitOrderOptimizedHelper->getQuoteObject($order->getQuoteId());
        $retailTransactionId = $this->getRetailTransactionIdByGtnNumber($quote->getGtn());
        if (!empty($retailTransactionId)) {
            $this->saveOrderWithNewStatus($order, $retailTransactionId, $quote);

            return true;
        }

        return false;
    }

    /**
     * Update Quote Status and Timeout Flag
     *
     * @param object $quote
     * @param bool|int|string $status
     * @param bool|int|string $isTimeout
     */
    public function updateQuoteStatusAndTimeoutFlag($quote, $status, $isTimeout)
    {
        if ($status) {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ': '
                .' Active Quote before transaction for'. self::QUOTE_ID .$quote->getId()
            );
        } else {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ': '
                .' Quote deactivated after transaction for'. self::QUOTE_ID .$quote->getId()
            );
        }

        try {
            $quote->setIsTimeout($isTimeout);
            $quote->setIsActive($status);
            $quote->save();
            if(!$this->marketPlaceHelper->isEssendantToggleEnabled()){
                $this->submitOrderHelper->saveQuoteByRepository($quote);
            }
        } catch (Exception $e) {
            $this->logger->critical(
                'Exception occurred while updating quote status for Quote Id : '. $quote->getId()
                . ':' . $e->getMessage()
            );
        }
    }

    /**
     * Finally order placed after transaction
     *
     * @param object $order
     * @throws Exception
     */
    public function finalizeOrder($order)
    {
        if ($order->hasInvoices() && $this->shipmentHelper->hasShipmentCreated($order)) {
            return true;
        }
        try {
            $this->logger->info(
                __METHOD__ . ':' . __LINE__. ': Finally processing order for order Id:' .$order->getId()
            );
            $quote = $this->submitOrderOptimizedHelper->getQuoteObject($order->getQuoteId());

            if (!$order->hasInvoices()) {
                // Generate order invoice
                $this->submitOrderHelper->generateInvoice($order->getId());
            }
            if (!$this->shipmentHelper->hasShipmentCreated($order)) {
                // Create order shipment
                $shipmentCreated = $this->submitOrderHelper->createShipment($quote, $order->getId());

                if (!$shipmentCreated) {
                    // push order id in retry shipment queue
                    $this->logger->info(__METHOD__ . ':' . __LINE__.
                        ': Shipment creation failed for the order id: '. $order->getId());
                    $messageRequest = ['orderId' => $order->getId(), 'counter' => 0];
                    $this->submitOrderHelper->pushOrderIdInQueueForShipmentCreation(json_encode($messageRequest));
                    return false;
                }
            }

            // Update data in order producing address table
            $this->submitOrderOptimizedHelper->updateOrderProducingAddressDataAfterShipment($order);
            $this->submitOrderHelper->reorderInstanceSave($order->getId());
            $this->submitOrderHelper->cleanProductItemInstance($order->getQuoteId());
            $this->logger->info(__METHOD__ . ':' . __LINE__. ': Finally order processed for order Id:' .
                $order->getId());

            return true;
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__. ': Issue during Order finally place for order Id: '
                .$order->getId(). ' Error'. $e->getMessage()
            );
        }
    }

    /**
     * Get rate quote id from rate quote response
     *
     * @param array $rateQuoteResponse
     * @return string|null
     */
    public function getRateQuoteId($rateQuoteResponse)
    {
        return $this->submitOrderHelper->getRateQuoteId($rateQuoteResponse);
    }

    /**
     * Get Production Location
     * @return string||null
     */
    public function getProductionLocationId()
    {
        return $this->checkoutSession->getProductionLocationId() ?? null;
    }

    /**
     * Get product Line items with ePro orders from rateQuote Response
     * @param object $rateQuoteResponse
     */
    public function getProductLinesDetails($rateQuoteResponse)
    {
        return $this->submitOrderOptimizedHelper->getProductLinesDetails($rateQuoteResponse);
    }
}
