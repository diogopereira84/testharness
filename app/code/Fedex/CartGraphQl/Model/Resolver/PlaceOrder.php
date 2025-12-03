<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Pedro Basseto <pbasseto@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Exception;
use Fedex\CartGraphQl\Model\PlaceOrder\SubmitOrder;
use Fedex\GraphQl\Exception\GraphQlInStoreException;
use Fedex\GraphQl\Exception\GraphQlRAQMissingIdException;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Shipment\Helper\ShipmentEmail;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Fedex\CartGraphQl\Model\PlaceOrder\PoliticalDisclosureService;

class PlaceOrder extends AbstractResolver
{
    private $cart;

    private const ORDER_CONFIRMED_STATUS = 'confirmed';
    public const FUSE_ORDER_EMAIL_ENABLED = 'fedex/transactional_email/fuse_quote_order_confirmation_email_enable';
    private const QUOTE_STATUS_APPROVED = 'quote_approved';

    /**
     * @param GetCartForUser $getCartForUser
     * @param SubmitOrder $submitOrder
     * @param QuoteIdMask $quoteIdMaskResource
     * @param QuoteFactory $quoteFactory
     * @param FuseBidViewModel $fuseBidViewModel
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param InstoreConfig $instoreConfig
     * @param PoliticalDisclosureService $politicalDisclosureService
     * @param ShipmentEmail $_shipmentEmail
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationComposite $validationComposite
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param Order $order
     * @param ScopeConfigInterface $configInterface
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param FuseBidHelper $fuseBidHelper
     * @param array $validations
     */
    public function __construct(
        private readonly GetCartForUser                     $getCartForUser,
        private readonly SubmitOrder                        $submitOrder,
        private readonly QuoteIdMask                        $quoteIdMaskResource,
        private readonly QuoteFactory                       $quoteFactory,
        private readonly FuseBidViewModel                   $fuseBidViewModel,
        private readonly UploadToQuoteViewModel             $uploadToQuoteViewModel,
        private readonly InstoreConfig                      $instoreConfig,
        private readonly PoliticalDisclosureService         $politicalDisclosureService,
        private ShipmentEmail                               $_shipmentEmail,
        RequestCommandFactory                               $requestCommandFactory,
        ValidationComposite                                 $validationComposite,
        BatchResponseFactory                                $batchResponseFactory,
        LoggerHelper                                        $loggerHelper,
        NewRelicHeaders                                     $newRelicHeaders,
        public Order                                        $order,
        protected ScopeConfigInterface                      $configInterface,
        protected FuseBidGraphqlHelper $fuseBidGraphqlHelper,
        protected FuseBidHelper $fuseBidHelper,
        array                                               $validations = [],
    )
    {
        parent::__construct(
            $requestCommandFactory,
            $batchResponseFactory,
            $loggerHelper,
            $validationComposite,
            $newRelicHeaders,
            $validations,
            $_shipmentEmail,
            $configInterface,
            $fuseBidGraphqlHelper,
            $fuseBidHelper,
            $order
        );
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     * @throws GraphQlFujitsuResponseException
     * @throws GraphQlInStoreException
     * @throws GraphQlRAQMissingIdException
     * @throws CouldNotSaveException
     */
    public function proceed(
        ContextInterface $context,
        Field            $field,
        array            $requests,
        array            $headerArray
    ): BatchResponse
    {
        $responseData = $this->batchResponseFactory->create();
        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);

        foreach ($requests as $request) {
            try {
                $args = $request->getArgs();

                $maskedCartId = $args['input']['cart_id'];
                $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedCartId);

                $quote = $this->quoteFactory->create()->load($quoteId);
                if ($quote->getIsBid() && $this->fuseBidHelper->isToggleTeamMemberInfoEnabled() && isset($args['input']['comment'])) {
                    $commentText = $args['input']['comment'];
                    $this->fuseBidGraphqlHelper->validateComment($commentText);
                    $this->fuseBidGraphqlHelper->saveNegotiableQuoteComment($quoteId, $commentText, self::QUOTE_STATUS_APPROVED,);
                }

                $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
                $this->cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
                $note = $args['input']['notes'] ?? null;

                $response = $this->submitOrder->execute($this->cart, $note);

                if ((isset($response['error']) && $response['error']) ||
                    (isset($response[0]['error']) && $response[0]['error'])
                ) {
                    if (isset($response['response']['errors'][0]['message'])) {
                        $this->loggerHelper->critical(
                            __CLASS__ . ' : ' . __METHOD__ . ':' . __LINE__ .
                                PHP_EOL . 'response: ' . json_encode($response),
                            $headerArray
                        );
                        throw new GraphQlInStoreException($response['response']['errors'][0]['message']);
                    }
                    $this->loggerHelper->critical(
                        __CLASS__ . ' : ' . __METHOD__ . ':' . __LINE__ .
                            PHP_EOL . 'response: ' . json_encode($response),
                        $headerArray
                    );
                    throw new GraphQlInStoreException($response['message'] ?? $response['msg'] ?? '');
                }
                $transactionId = $response['rateQuoteResponse']['transactionId'] ??
                    $response[0]['response']['transactionId'] ?? null;
                if (!$transactionId) {
                    $this->loggerHelper->critical(
                        __CLASS__ . ' : ' . __METHOD__ . ':' . __LINE__ .
                            PHP_EOL . 'response: ' . json_encode($response),
                        $headerArray
                    );
                    throw new GraphQlRAQMissingIdException("Transaction Id Missing from RAQ response.");
                }
            } catch (GraphQlFujitsuResponseException $e) {
                $this->loggerHelper->error(
                    __CLASS__ . ' : ' . __METHOD__ . ':' . __LINE__ . $e->getMessage() .
                        PHP_EOL . 'response: ' . json_encode($response ?? []),
                    $headerArray
                );
                $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
                throw new GraphQlFujitsuResponseException(__($e->getMessage()));
            } catch (GraphQlRAQMissingIdException $e) {
                $this->loggerHelper->critical(
                    __CLASS__ . ' : ' . __METHOD__ . ':' . __LINE__ . $e->getMessage() .
                        PHP_EOL . 'response: ' . json_encode($response ?? []),
                    $headerArray
                );
                $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
                throw $e;
            } catch (Exception $e) {
                $this->loggerHelper->error(
                    __CLASS__ . ' : ' . __METHOD__ . ':' . __LINE__ . $e->getMessage() .
                        PHP_EOL . 'response: ' . json_encode($response ?? []),
                    $headerArray
                );
                $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
                throw new GraphQlInStoreException($e->getMessage());
            }

            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
            if ($this->configInterface->getValue(self::FUSE_ORDER_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE) && $this->cart->getIsBid()) {
                $orderDetails = $this->order->loadByIncrementId($this->cart->getReservedOrderId());
                $this->_shipmentEmail->sendEmail(
                    self::ORDER_CONFIRMED_STATUS,
                    $orderDetails->getId(),
                    null,
                    null,
                    true
                );
            }
            if (isset($args['input']['political_campaign_disclosure']) && $this->instoreConfig->isEnablePoliticalDisclosureInPlaceOrder()) {
                $disclosureInput = $args['input']['political_campaign_disclosure'];
                $this->politicalDisclosureService->setDisclosureDetails($disclosureInput, $this->cart->getReservedOrderId());
            }
            $responseValue = [
                "order" => [
                    "order_number" => $this->cart->getReservedOrderId()
                ],
                "transaction_id" => $transactionId
            ];
            $responseData->addResponse($request, $responseValue);
            if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && $quote->getIsBid()) {
                $this->uploadToQuoteViewModel->updateQuoteStatusByKey(
                    $quoteId,
                    NegotiableQuoteInterface::STATUS_ORDERED,
                    true
                );
            }
        }
        return $responseData;
    }
}
