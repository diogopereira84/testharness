<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model\Resolver;

use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Quote\Model\Quote\ItemFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use \Psr\Log\LoggerInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Model\AlertsMapper;

class UpdateQuoteLineItem implements ResolverInterface
{
    /** @var $allowedQuoteActions  */
    private $allowedQuoteActions = ['save', 'sent_to_customer', 'close', 'revision_requested'];
    private $newAllowedQuoteActions = ['save', 'sent_to_customer', 'close', 'revision_requested', 'nbc_support', 'nbc_priced'];

    public const STATUS_NBC_SUPPORT = 'nbc_support';
    public const STATUS_NBC_PRICED = 'nbc_priced';

    public const STATUS_ARRAY = ["sent_to_customer", "nbc_priced", "nbc_support"];

    /** @var $allowedItemAction  */
    private $allowedItemAction = ['update', 'delete', 'add'];

    /** @var $rateResponse  */
    protected $rateResponse;

    /** @var string */
    public const TIGER_FEATURE_B_2645989 = 'tiger_feature_b2645989';

    /**
     * array of coupon related alerts
     */
    public array $couponAlerts = [];

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param SerializerInterface $serializer
     * @param QuoteIdMask $quoteIdMaskResource
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param ProductRepositoryInterface $productRepository
     * @param ItemFactory $itemFactory
     * @param FuseBidViewModel $fuseBidViewModel
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param LoggerInterface $logger
     * @param AdminConfigHelper $adminConfigHelper
     * @param ToggleConfig $toggleConfig
     * @param AlertsMapper $alertsMapper
     */
    public function __construct(
        protected CartRepositoryInterface $quoteRepository,
        protected SerializerInterface $serializer,
        private QuoteIdMask $quoteIdMaskResource,
        private GraphqlApiHelper $graphqlApiHelper,
        protected QuoteEmailHelper $quoteEmailHelper,
        protected ProductRepositoryInterface $productRepository,
        protected ItemFactory $itemFactory,
        protected FuseBidViewModel $fuseBidViewModel,
        protected LoggerHelper $loggerHelper,
        protected NewRelicHeaders $newRelicHeaders,
        protected LoggerInterface $logger,
        protected AdminConfigHelper $adminConfigHelper,
        private readonly ToggleConfig $toggleConfig,
        private readonly AlertsMapper $alertsMapper
    ) {}

    /**
     * Resolve Method
     *
     * @throws GraphQlInputException
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $mutationName = $field->getName() ?? '';
        $headerArray = $this->newRelicHeaders->getHeadersForMutation($mutationName);
        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($args['input']['uid']);
        $logData['quoteId'] = $quoteId;
        $logData['query'] = $info->fieldName;
        $logData['variables'] = json_encode($args);
        $this->graphqlApiHelper->addLogsForGraphqlApi($logData);
        $quoteAction = $args['input']['quote_action'];
        $comment = $args['input']['comment'] ?? "";
        $quoteItems = $args['input']['quote_items'] ?? [];
        $this->validateInputs($quoteAction, $comment, $quoteItems);
        $quote = $this->quoteRepository->get($quoteId);
        if($this->adminConfigHelper->isToggleB2564807Enabled()){

            if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && isset($args['input']['quote_action'])
            && ($args['input']['quote_action'] == self::STATUS_NBC_SUPPORT))
            {
                $nbcRequiredInput = true;
                $this->saveNbcRequiredInput($nbcRequiredInput, $quote);
            }
        }else{
            if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && isset($args['input']['nbc_required'])) {
                $nbcRequiredInput = $args['input']['nbc_required'];
                $this->saveNbcRequiredInput($nbcRequiredInput, $quote);
            }
        }

        if ($this->adminConfigHelper->isToggleB2564807Enabled()){
            if (in_array($quoteAction, self::STATUS_ARRAY)) {
                $this->validateStatusChangeAction($quote, $quoteAction);
            }
        }else{
            if ($quoteAction == "sent_to_customer") {
                $this->validateStatusChangeAction($quote, $quoteAction);
            }
        }
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_FEATURE_B_2645989)) {
            if (isset($args['input']['coupon_code'])) {
                $couponCode = trim((string)$args['input']['coupon_code']);
                $this->saveCouponCode($quote, $couponCode);
            }
        }

        $triggerCancellationEmail = $args['input']['trigger_cancellation_email'] ?? true;

        $this->processQuoteItems($quote, $quoteItems, $quoteAction, $triggerCancellationEmail);
        $this->saveComment($comment, $quoteAction, $quoteItems, $quoteId);

        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
        $quoteResult = $this->buildResult($quoteId, $quote);
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_FEATURE_B_2645989)) {
            if (isset($args['input']['coupon_code']) && !empty($this->getCouponAlerts())) {
                $quoteResult['alerts'] = $this->alertsMapper->map($this->getCouponAlerts());
            }
        }
        return $quoteResult;
    }

    /**
     * Save nbc_required input
     *
     * @param bool $nbcRequiredInput
     * @param obj $quote
     * @return void
     */
    private function saveNbcRequiredInput($nbcRequiredInput, $quote)
    {
            $quote->setNbcRequired($nbcRequiredInput);
            $quote->save($quote);
    }

    /**
     * Validate Status Change Action
     *
     * @param obj $quote
     * @return void
     */
    private function validateStatusChangeAction($quote)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($this->adminConfigHelper->isToggleB2564807Enabled()){
            if ($negotiableQuote->getStatus()=='ordered') {
                throw new GraphQlInputException(__('You cannot change quote status from ordered to SENT, NBC SUPPORT & NBC PRICED'));
            }
        }else{
            if ($negotiableQuote->getStatus()=='ordered') {
                throw new GraphQlInputException(__('You cannot change quote status from ordered to SENT'));
            }
        }
    }

    /**
     * Validate API Inputs
     *
     * @param string $quoteAction
     * @param string $comment
     * @param string $quoteItems
     * @return void
     */
    private function validateInputs($quoteAction, $comment, $quoteItems)
    {
        $this->validateComment($comment, $quoteItems);
        $this->validateQuoteAction($quoteAction, $quoteItems);
        $this->validateQuoteItems($quoteItems, $quoteAction);
    }

    /**
     * Validate quote comment input
     *
     * @param string $comment
     * @param array $quoteItems
     * @return void
     */
    private function validateComment($comment, $quoteItems)
    {
        if (empty($comment) && empty($quoteItems)) {
            throw new GraphQlInputException(__('Both comment and quote_items cannot be empty'));
        }
    }

    /**
     * Validate quote action
     *
     * @param string $quoteAction
     * @param array $quoteItems
     * @return void
     */
    private function validateQuoteAction($quoteAction, $quoteItems)
    {
        if ($this->adminConfigHelper->isToggleB2564807Enabled()){
            if (!in_array($quoteAction, $this->newAllowedQuoteActions)) {
            throw new GraphQlInputException(__('Invalid Quote action. Allowed values are: ' . implode(
                ', ',
                $this->newAllowedQuoteActions
            )));
            }
            if (in_array($quoteAction, self::STATUS_ARRAY) && empty($quoteItems)) {
                throw new GraphQlInputException(__('quote_items cannot be empty'));
            }
        }else{
            if (!in_array($quoteAction, $this->allowedQuoteActions)) {
            throw new GraphQlInputException(__('Invalid Quote action. Allowed values are: ' . implode(
                ', ',
                $this->allowedQuoteActions
            )));
            }
            if (($quoteAction == 'sent_to_customer') && empty($quoteItems)) {
                throw new GraphQlInputException(__('quote_items cannot be empty'));
            }
        }
    }

    /**
     * Update Quote Items
     *
     * @param array $quoteItems
     * @param array $quoteAction
     * @throws GraphQlInputException
     * @return void
     */
    private function validateQuoteItems($quoteItems, $quoteAction)
    {
        foreach ($quoteItems as $quoteItem) {
            $this->validateItemAction($quoteItem, $quoteAction);
            $this->validateUpdateAction($quoteItem, $quoteAction);
            $this->validatePriceableItem($quoteItem, $quoteAction);
            $this->validateSentAction($quoteItem, $quoteAction);
        }
    }

    /**
     * Validate Item Action
     *
     * @param array $quoteItem
     * @throws GraphQlInputException
     * @return void
     */
    private function validateItemAction($quoteItem)
    {
        if (!in_array($quoteItem['item_action'], $this->allowedItemAction)) {
            throw new GraphQlInputException(__('Invalid item action. Allowed values are: ' . implode(
                ', ',
                $this->allowedItemAction
            )));
        }
    }

    /**
     * Validate Update Action
     *
     * @param array $quoteItem
     * @param string $quoteAction
     * @throws GraphQlInputException
     * @return void
     */
    private function validateUpdateAction($quoteItem, $quoteAction)
    {
        if ($this->adminConfigHelper->isToggleB2564807Enabled()){
            if (($quoteAction == 'save' || $quoteAction == 'sent_to_customer' || $quoteAction == self::STATUS_NBC_PRICED || $quoteAction == self::STATUS_NBC_SUPPORT)
                && ($quoteItem['item_action'] == "update")
                && (empty($quoteItem['product']) || empty($quoteItem['item_id']))
            ) {
                throw new GraphQlInputException(__("item_id and product are essential to update the item."));
            }
        }else{
            if (($quoteAction == 'save' || $quoteAction == 'sent_to_customer')
                && ($quoteItem['item_action'] == "update")
                && (empty($quoteItem['product']) || empty($quoteItem['item_id']))
            ) {
                throw new GraphQlInputException(__("item_id and product are essential to update the item."));
            }
        }
    }

    /**
     * Validate Priceable Item
     *
     * @param array $quoteItem
     * @param string $quoteAction
     * @throws GraphQlInputException
     * @return void
     */
    private function validatePriceableItem($quoteItem, $quoteAction)
    {
        if ($this->adminConfigHelper->isToggleB2564807Enabled()){
            if (!empty($quoteItem['product'])) {
                $productJson = json_decode($quoteItem['product'], true) ?? [];
                if (!$productJson['priceable'] && in_array($quoteAction, self::STATUS_ARRAY)) {
                    throw new GraphQlInputException(__("For sent_to_customer, only priceable items should be there"));
                }
            }
        }else{
            if (!empty($quoteItem['product'])) {
                $productJson = json_decode($quoteItem['product'], true) ?? [];
                if (!$productJson['priceable'] && $quoteAction == "sent_to_customer") {
                    throw new GraphQlInputException(__("For sent_to_customer, only priceable items should be there"));
                }
            }
        }
    }

    /**
     * Validate Sent Action
     *
     * @param array $quoteItem
     * @param string $quoteAction
     * @throws GraphQlInputException
     * @return void
     */
    private function validateSentAction($quoteItem, $quoteAction)
    {
        if ($quoteAction == 'sent_to_customer'
            && ($quoteItem['item_action'] == "add" || $quoteItem['item_action'] == "delete")
        ) {
            throw new GraphQlInputException(__("For sent_to_customer, item action cannot be add or delete"));
        }
    }

    /**
     * Process quote items
     *
     * @param obj $quote
     * @param array $quoteItems
     * @param string $quoteAction
     * @param bool $triggerCancellationEmail (optional, default true)
     * @return void
     */
    private function processQuoteItems($quote, $quoteItems, $quoteAction, $triggerCancellationEmail = true)
    {
        $items = $quote->getAllItems();
        $quoteItemIds = array_column($quoteItems, 'item_id');
        $itemIds = array_map(function ($item) {
            return $item->getItemId();
        }, $items);
        $missingItemIds = array_diff($quoteItemIds, $itemIds);
        $this->validateMissingItems($missingItemIds);
        $this->processQuoteActions($quote, $quoteAction, $quoteItems, $triggerCancellationEmail);
    }

      /**
       * Validate missing Items
       *
       * @param array $missingItemIds
       * @return void
       */
    private function validateMissingItems($missingItemIds)
    {
        if (!empty($missingItemIds)) {
            foreach ($missingItemIds as $missingItem) {
                throw new GraphQlInputException(
                    __('Provided item_id %1 does not belong to the given quote', $missingItem)
                );
            }
        }
    }

    /**
     * Process quote actions
     *
     * @param obj $quote
     * @param string $quoteAction
     * @param array $quoteItems
     * @param bool $triggerCancellationEmail (optional, defaults to true)
     * @return void
     */
    private function processQuoteActions($quote, $quoteAction, $quoteItems, $triggerCancellationEmail = true)
    {
        if ($this->graphqlApiHelper->quotebiddinginstoreupdatesFixToggle() && $quoteAction == 'revision_requested') {
            $this->processQuoteItemsActionsForRevisionRequested($quote, $quoteItems);
        } else if ($quoteAction == 'save') {
            $this->processQuoteItemsActionsForSave($quote, $quoteItems);
        } else if ($quoteAction == 'sent_to_customer') {
            $this->processQuoteItemsActionsForSent($quote, $quoteItems);
        } else if ($quoteAction == 'close') {
            $this->processQuoteCloseAction($quote, $quoteItems, $triggerCancellationEmail);
        } else if ($quoteAction == self::STATUS_NBC_SUPPORT) {
            $this->processQuoteItemsActionsForNbcSupport($quote, $quoteItems);
        } else if ($quoteAction == self::STATUS_NBC_PRICED) {
            $this->processQuoteItemsActionsForNbcPriced($quote, $quoteItems);
        } else {
            throw new GraphQlInputException(__('Invalid quote action'));
        }
    }

    /**
     * Process quote Item Actions
     *
     * @param obj $quote
     * @param array $quoteItems
     * @return void
     */
    private function processQuoteItemsActionsForSave($quote, $quoteItems)
    {
        if ($this->graphqlApiHelper->quotesavebeforeratequoteFixToggle() && !$quote->getFedexAccountNumber()){
            foreach ($quoteItems as $quoteItem) {
                $this->processQuoteItemAction($quote, $quoteItem, 'update');
            }
            $saveFlag = $this->callRateAPI($quote, $quoteItems, 'save');
        }else{
            $saveFlag = $this->callRateAPI($quote, $quoteItems, 'save');
            foreach ($quoteItems as $quoteItem) {
                $this->processQuoteItemAction($quote, $quoteItem, $saveFlag);
            }
        }
    }

    /**
     * Process Quote Item Action
     *
     * @param obj $quote
     * @param array $quoteItem
     * @param bool $saveFlag
     * @throws GraphQlInputException
     * @return void
     */
    private function processQuoteItemAction($quote, $quoteItem, $saveFlag)
    {
        switch ($quoteItem['item_action']) {
            case 'add':
                $this->processAddAction($quote, $quoteItem, $saveFlag);
                break;
            case 'update':
                $this->processUpdateAction($quote, $quoteItem, $saveFlag);
                break;
            case 'delete':
                $this->processDeleteAction($quote, $quoteItem, $saveFlag);
                break;
        }
    }

    /**
     * Process Add Action
     *
     * @param obj $quote
     * @param array $quoteItem
     * @param bool $saveFlag
     * @return void
     */
    private function processAddAction($quote, $quoteItem, $saveFlag)
    {
        if ($saveFlag) {
            $this->addNewQuoteItem($quote, $quoteItem);
        }
    }

    /**
     * Process Update Action
     *
     * @param obj $quote
     * @param array $quoteItem
     * @param bool $saveFlag
     * @return void
     */
    private function processUpdateAction($quote, $quoteItem, $saveFlag)
    {
        if ($saveFlag) {
            $this->updateQuoteItemOptions($quoteItem, $quote);
        }
    }

    /**
     * Process Delete Action
     *
     * @param obj $quote
     * @param array $quoteItem
     * @param bool $saveFlag
     * @throws GraphQlInputException
     * @return void
     */
    private function processDeleteAction($quote, $quoteItem, $saveFlag)
    {
        $itemsCount = count($quote->getAllItems());
        if ($itemsCount === 1) {
            throw new GraphQlInputException(__("Cannot delete the only item in the quote"));
        }
        if ($saveFlag) {
            try {
                $quote->removeItem($quoteItem['item_id']);
                $quote->save();
            } catch (\Exception $exception) {
                throw new GraphQlInputException(__("Error while deleting quote_item"));
            }
        }
    }

    /**
     * Process quote Item Actions
     *
     * @param obj $quote
     * @param array $quoteItems
     * @return void
     */
    private function processQuoteItemsActionsForSent($quote, $quoteItems)
    {
        $response = $this->callRateAPI($quote, $quoteItems, 'sent_to_customer');
        if ($response) {
            foreach ($quoteItems as $quoteItem) {
                if ($quoteItem['item_action'] == "update") {
                    $this->updateQuoteItemOptions($quoteItem, $quote);
                }
            }
            $this->graphqlApiHelper
            ->changeQuoteStatus($quote, NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
            $this->sendEmail($quote->getId(), 'submitted_by_admin');
        } else {
            throw new GraphQlInputException(
                __("Not all items are priceable, therefore, their status cannot be changed to SENT")
            );
        }
    }

    /**
     * Process quote Item Actions
     *
     * @param obj $quote
     * @param array $quoteItems
     * @return void
     */
    private function processQuoteItemsActionsForNbcSupport($quote, $quoteItems)
    {
        foreach ($quoteItems as $quoteItem) {
            $this->processQuoteItemAction($quote, $quoteItem, 'update');
        }
        $saveFlag = $this->callRateAPI($quote, $quoteItems, self::STATUS_NBC_SUPPORT);
        if ($quote->getIsActive()) {
            $quote->setIsActive(0);
        }
        $this->graphqlApiHelper
            ->changeQuoteStatus($quote, self::STATUS_NBC_SUPPORT);
        $this->sendEmail($quote->getId(), self::STATUS_NBC_SUPPORT);
    }

    /**
     * Process quote Item Actions
     *
     * @param obj $quote
     * @param array $quoteItems
     * @return void
     */
    private function processQuoteItemsActionsForNbcPriced($quote, $quoteItems)
    {
        foreach ($quoteItems as $quoteItem) {
            $this->processQuoteItemAction($quote, $quoteItem, 'update');
        }
        $saveFlag = $this->callRateAPI($quote, $quoteItems, self::STATUS_NBC_PRICED);
        if ($quote->getIsActive()) {
            $quote->setIsActive(0);
        }
        $this->graphqlApiHelper
            ->changeQuoteStatus($quote, self::STATUS_NBC_PRICED);
        $this->sendEmail($quote->getId(), self::STATUS_NBC_PRICED);
    }

    /**
     * Process actions when quote is closed
     *
     * @param obj $quote
     * @param array $quoteItems
     * @param bool $triggerCancellationEmail (optional, defaults to true)
     * @return void
     */
    private function processQuoteCloseAction($quote, $quoteItems, $triggerCancellationEmail = true)
    {
        $saveFlag = $this->callRateAPI($quote, $quoteItems, 'save');
        if ($saveFlag) {
            $this->graphqlApiHelper->changeQuoteStatus($quote, NegotiableQuoteInterface::STATUS_CLOSED);
            if ($triggerCancellationEmail) {
                $this->sendEmail($quote->getId(), 'close');
            }
        }
    }

    /**
     * Save Comments
     *
     * @param string $comment
     * @param string $quoteAction
     * @param array $quoteItems
     * @param int $quoteId
     * @return void
     */
    private function saveComment($comment, $quoteAction, $quoteItems, $quoteId)
    {
        if ($comment) {
            $type = $this->getType($quoteAction, $quoteItems);
            $this->graphqlApiHelper->setQuoteNotes($comment, $quoteId, $type);
        }
    }

    /**
     * Determines the type of action based on the quote items and action.
     *
     * @param string $quoteAction
     * @param array $quoteItems
     * @return string
     */
    private function getType($quoteAction, $quoteItems)
    {
        if ($quoteAction == "close") {
            return "quote_closed";
        }
        if ($quoteAction == "save" && !empty($quoteItems)) {
            return "quote_updated";
        } elseif ($quoteAction == "save" && empty($quoteItems)) {
            return "note_added";
        }
        if ($quoteAction == "sent_to_customer") {
            return "sent_to_customer";
        }
        if ($quoteAction == "nbc_support") {
            return "nbc_support";
        }
        if ($quoteAction == "nbc_priced") {
            return "nbc_priced";
        }
    }

    /**
     * Call Rate API
     *
     * @param obj $quote
     * @param array $quoteItemsArray
     * @param string $quoteAction
     * @return boolean
     * @throws GraphQlInputException
     */
    public function callRateAPI($quote, $quoteItemsArray, $quoteAction)
    {
        $quoteItemsArray[0]['quote_action'] = $quoteAction;
        $this->rateResponse = $this->graphqlApiHelper->getRateResponse($quote, $quoteItemsArray);
        $this->setRateQuoteAlertsResponse($this->rateResponse);
        if ($this->toggleConfig->getToggleConfigValue(self::TIGER_FEATURE_B_2645989)) {
            if (!empty($this->rateResponse['output']['alerts'])) {
                $this->handleCouponCodeAlerts($quote, $this->rateResponse['output']['alerts']);
            }
        }
        if ($quoteAction == 'sent_to_customer') {
            return $this->validateAllItemsPriceable($this->rateResponse);
        } elseif ($quoteAction == 'save' && !empty($this->rateResponse['output']['rateQuote'])) {
            return true;
        }
        return false;
    }

    /**
     * Validate All Items Priceable
     *
     * @param array $rateResponse
     * @return boolean
     */
    private function validateAllItemsPriceable($rateResponse)
    {
        $allItemsPriceable = true;

        if (isset($rateResponse['output']['rateQuote']['rateQuoteDetails'][0]['productLines'])) {
            $productLines = $rateResponse['output']['rateQuote']['rateQuoteDetails'][0]['productLines'];

            foreach ($productLines as $prodLineData) {
                if (!$prodLineData['priceable']) {
                    $allItemsPriceable = false;
                    break;
                }
            }
        }

        return $allItemsPriceable;
    }

    /**
     * Update Quote Item Json
     *
     * @param array $quoteItem
     * @param obj $quote
     * @return void
     */
    private function updateQuoteItemOptions($quoteItem, $quote)
    {
        $items = $quote->getAllItems();
        foreach ($items as $item) {
            if ($item->getMiraklOfferId()) {
                continue;
            }
            $quoteOption = $item->getOptionByCode('info_buyRequest');
            if ($quoteItem['item_id'] == $quoteOption->getItemId()) {
                $newProductJson = $quoteItem['product'];
                $unserilizedProductJson = $this->serializer->unserialize($newProductJson);
                $itemQty = $unserilizedProductJson['qty'] ?? 1;
                $additionalOptions = $quoteOption->getValue();
                $decodedData = $this->serializer->unserialize($additionalOptions);
                $decodedData['external_prod'][0] = $unserilizedProductJson;
                $encodedData = $this->serializer->serialize($decodedData);
                $quoteOption->setValue($encodedData)->save();
                $item->setQty($itemQty);
                $item->save();
            }
        }
        if ((int)$this->graphqlApiHelper->isTigerRetailUploadToQuoteEnabled()) {
            $quote->collectTotals();
            $quote->save();
        }
    }

    /**
     * Send Email
     *
     * @param int $quoteId
     * @param string $status
     * @return void
     */
    private function sendEmail($quoteId, $status)
    {
        $quoteData = [
            'quote_id' => $quoteId
        ];
        if ($status == 'close') {
            $quoteData['status'] = 'declined_by_team';
        } elseif ($status == 'submitted_by_admin') {
            $quoteData['status'] = NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN;
        } elseif ($status == self::STATUS_NBC_SUPPORT) {
            $quoteData['status'] = self::STATUS_NBC_SUPPORT;
        } elseif ($status == self::STATUS_NBC_PRICED) {
            $quoteData['status'] = self::STATUS_NBC_PRICED;
        } elseif ($status == AdminConfigHelper::CHANGE_REQUEST) {
            $quoteData['status'] = AdminConfigHelper::CHANGE_REQUEST;
        }

        $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
    }
    /**
     * Add Quote Item
     *
     * @param obj $quote
     * @param array $quoteItem
     * @return void
     */
    private function addNewQuoteItem($quote, $quoteItem)
    {
        $productId =  $this->graphqlApiHelper->getFuseAddToQuoteProduct();
        $product = $this->productRepository->getById($productId);
        $randomNumber = rand(1, 100000000000000);
        $customOptions = [
            'label' => 'fxoProductInstance',
            'value' =>  $productId . $randomNumber,
        ];
        $product->addCustomOption('custom_option', $this->serializer->serialize($customOptions));
        $item = $this->itemFactory->create();
        $newProductJson = $quoteItem['product'];
        $unserilizedProductJson = $this->serializer->unserialize($newProductJson);
        $itemQty = $unserilizedProductJson['qty'] ?? 1;
        $decodedData = [];
        $decodedData['external_prod'][0] = $unserilizedProductJson;
        $encodedData = $this->serializer->serialize($decodedData);
        $item->addOption([
            'product_id' => $productId ,
            'code' => 'info_buyRequest',
            'value' => $encodedData,
        ]);
        $item ->setProduct($product);
        $item ->setQty($itemQty);
        $quote->addItem($item);
        $quote->save();
    }

    /**
     * Build Result
     *
     * @param int $quoteId
     * @param obj $quote
     * @return array
     * @throws GraphQlInputException
     */
    private function buildResult($quoteId, $quote)
    {
        $quoteInfo = $this->graphqlApiHelper->getQuoteInfo($quote);
        $result = [
            'quote_id' => $quoteInfo['quote_id'],
            'quote_status' => $quoteInfo['quote_status'],
            'hub_centre_id' => $quoteInfo['hub_centre_id'],
            'location_id' => $quoteInfo['location_id'],
            'quote_creation_date' => $quoteInfo['quote_creation_date'],
            'quote_updated_date' =>  $quoteInfo['quote_updated_date'],
            'quote_submitted_date' =>  $quoteInfo['quote_submitted_date'],
            'quote_expiration_date' => $quoteInfo['quote_expiration_date'],
            'fxo_print_account_number' => $this->graphqlApiHelper->getFxoAccountNumberOfQuote($quote),
            'company' => $this->graphqlApiHelper->getQuoteCompanyName($quote),
            'coupon_code' => $quote->getCouponCode(),
            'contact_info' => $this->graphqlApiHelper->getQuoteContactInfo($quote),
            'rateSummary' =>  $this->graphqlApiHelper->getRateSummaryData($this->rateResponse),
            'line_items' => $this->graphqlApiHelper->getQuoteLineItems($quote, $this->rateResponse),
            'activities' => $this->graphqlApiHelper->getQuoteNotes($quoteId),
            'lte_identifier' => $quote->getLteIdentifier() ?? null

        ];
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $result['is_bid'] = $quote->getIsBid();
            $result['nbc_required'] = $quote->getNbcRequired();
        }

        return $result;
    }

    /**
     * Process quote Item Actions for revision
     *
     * @param obj $quote
     * @param array $quoteItems
     * @return void
     */
    private function processQuoteItemsActionsForRevisionRequested($quote, $quoteItems)
    {
        foreach ($quoteItems as $quoteItem) {
            $this->processQuoteItemAction($quote, $quoteItem, 'update');
        }
        $saveFlag = $this->callRateAPI($quote, $quoteItems, 'save');
        if ($quote->getIsActive()) {
            $quote->setIsActive(0);
        }
        $this->graphqlApiHelper
            ->changeQuoteStatus($quote, NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER);
        if ($this->adminConfigHelper->isToggleD235696Enabled()) {
           $this->sendEmail($quote->getId(), AdminConfigHelper::CHANGE_REQUEST);
        }
    }

    /**
     * @param $quote
     * @param $couponCode
     * @return void
     */
    public function saveCouponCode($quote, $couponCode): void
    {
        $quote->setCouponCode((string)$couponCode);
        $quote->save($quote);

        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($negotiableQuote && $negotiableQuote->getSnapshot()) {
            $snapshot = $this->serializer->unserialize($negotiableQuote->getSnapshot());
            if (isset($snapshot['quote'])) {
                $snapshot['quote']['coupon_code'] = (string)$couponCode;
                $negotiableQuote->setSnapshot($this->serializer->serialize($snapshot));
                $negotiableQuote->save();
            }
        }

        $this->loggerHelper->info(
            __METHOD__ . ':' . __LINE__ . ' Coupon code set: ' . $couponCode
        );
    }

    /**
     * @param $quote
     * @param $alerts
     * @return void
     */
    private function handleCouponCodeAlerts($quote, $alerts): void
    {
        foreach ($alerts as $alert) {
            if (
                $alert['code'] === "COUPONS.CODE.INVALID" ||
                $alert['code'] === "MINIMUM.PURCHASE.REQUIRED"
            ) {
                $couponCode = "";
                $this->saveCouponCode($quote, $couponCode);
            }
        }
    }

    /**
     * @param $rateResponse
     * @return void
     */
    private function setRateQuoteAlertsResponse($rateResponse): void
    {
        $this->couponAlerts = $rateResponse["output"]["alerts"] ?? [];
    }

    /**
     * @return array
     */
    private function getCouponAlerts(): array
    {
        return $this->couponAlerts;
    }
}
