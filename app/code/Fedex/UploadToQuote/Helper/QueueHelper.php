<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Psr\Log\LoggerInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Fedex\B2b\Model\NegotiableQuoteManagement;

/**
 * Class for creating upload to quote queue
 */
class QueueHelper extends AbstractHelper
{
    public const DECLINED = 'declined';
    public const DELETE_ITEM = 'deleteItem';
    public const CHANGE_REQUESTED = 'changeRequested';
    public const FAILED_QUEUE = 'Upload to quote action queue is not set';
    public const SUCCESS_QUEUE = 'Upload to quote action queue is set';
    public const PROCESS_QUEUE_SUCCESS = 'Upload to quote action queue is processed';
    public const GRACE_PERIOD = 10;

    /**
     * QueueHelper Constructor
     *
     * @param Context $context
     * @param TimezoneInterface $timezoneInterface
     * @param CustomerSession $customerSession
     * @param AdminConfigHelper $adminConfigHelper
     * @param QuoteFactory $quoteFactory
     * @param FXORateQuote $fxoRateQuote
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param NegotiableQuoteFactory $negotiableQuoteFactory
     */
    public function __construct(
        Context $context,
        protected TimezoneInterface $timezoneInterface,
        protected CustomerSession $customerSession,
        protected AdminConfigHelper $adminConfigHelper,
        protected QuoteFactory $quoteFactory,
        protected FXORateQuote $fxoRateQuote,
        protected QuoteEmailHelper $quoteEmailHelper,
        protected SerializerInterface $serializer,
        protected LoggerInterface $logger,
        private GraphqlApiHelper $graphqlApiHelper,
        protected NegotiableQuoteFactory $negotiableQuoteFactory,
        protected NegotiableQuoteManagement $negotiableQuoteManagement
    ) {
        parent::__construct($context);
    }

    /**
     * Set upload to quote action queue
     *
     * @param array $post
     * @return array
     */
    public function setQueue($post)
    {
        $response = [
            "status" => 200,
            'message' => self::FAILED_QUEUE,
            'Queue' => false
        ];
        $todayDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        if ($post['action'] == self::DECLINED) {
            $response = $this->setDeclinedQueue($post, $todayDate);
        } elseif ($post['action'] == self::DELETE_ITEM) {
            $response = $this->setDeleteItemQueue($post, $todayDate);
        } elseif ($post['action'] == self::CHANGE_REQUESTED) {
            $response = $this->setChangeRequestedItemQueue($post, $todayDate);
        }

        return $response;
    }

    /**
     * Set upload to quote declined action queue
     *
     * @param array $post
     * @param string $todayDate
     * @return array
     */
    public function setDeclinedQueue($post, $todayDate)
    {
        $response = [
            "status" => 200,
            'message' => 'Order against this quote is already submitted.',
            'Queue' => 'declinedFailed'
        ];
        $quote = $this->negotiableQuoteFactory->create()->load($post['quoteId']);
        if ($quote->getStatus() != NegotiableQuoteInterface::STATUS_ORDERED) {
            $uploadToQuoteActionQueue = [
                'action' => $post['action'],
                'requestedDateTime' => $todayDate,
                'quoteId' => $post['quoteId'],
                'reasonForDeclining' => $post['reasonForDeclining'],
                'additionalComments' => $post['additionalComments'],
                'declinedDate' => $post['declinedDate'],
                'declinedTime' => $post['declinedTime'],
            ];
            $arrQueue = $this->makeQueue($uploadToQuoteActionQueue);
            
            $response = [
                "status" => 200,
                'message' => self::SUCCESS_QUEUE,
                'Queue' => $arrQueue,
                'leftDate' => $this->adminConfigHelper->getFormattedDate($post['declinedDate'], 'F j, Y'),
                'declinedProgessBarMsg' => 'You declined this quote on
                '.$this->adminConfigHelper->getFormattedDate($post['declinedDate'], 'd/m/y').'
                at '.$post['declinedTime'].'.'
            ];
        }

        return $response;
    }

    /**
     * Set upload to quote delete item action queue
     *
     * @param array $post
     * @param string $todayDate
     * @return array
     */
    public function setDeleteItemQueue($post, $todayDate)
    {
        $resoponse = [
            "status" => 200,
            'message' => self::FAILED_QUEUE,
            'Queue' => false,
        ];
        $quote = $this->quoteFactory->create()->load($post['quoteId']);
        $quoteId = $post['quoteId'];
        $itemId = $post['itemId'];
        $uploadToQuoteRequest = [
            'action' => $post['action'],
            'item_id' => $itemId
        ];
        $rateResponse = $this->fxoRateQuote->getFXORateQuote($quote, null, false, $uploadToQuoteRequest);
        
        if (isset($rateResponse['output']['rateQuote']) && $rateResponse['output']['rateQuote']) {
            $uploadToQuoteActionQueue = [
                'action' => $post['action'],
                'requestedDateTime' => $todayDate,
                'quoteId' => $quoteId,
                'itemId' => $itemId,
                'deletedDate' => $post['deletedDate'],
                'deletedTime' => $post['deletedTime'],
                'rateQuoteResponse' => $rateResponse['output']
            ];
            $arrQueue = $this->makeQueue($uploadToQuoteActionQueue);
            $resoponse = [
                "status" => 200,
                'message' => self::SUCCESS_QUEUE,
                'Queue' => $arrQueue,
                'rateQuoteResponse' => $rateResponse['output']
            ];
        }

        return $resoponse;
    }

    /**
     * Set upload to quote change requested action queue
     *
     * @param array $post
     * @param string $todayDate
     * @return array
     */
    public function setChangeRequestedItemQueue($post, $todayDate)
    {
        $this->customerSession->unsSiItems();
        $resoponse = [
            "status" => 200,
            'message' => self::FAILED_QUEUE,
            'Queue' => false,
        ];

        if (!empty($post['items']) && count($post['items']) > 1) {
            for ($i=0; $i < count($post['items']); $i++) {
                if ($post['items'][$i]['name'] == 'si') {
                    $siDataArr[] = [
                        'si' => $post['items'][$i]['value'],
                        'item_id' => $post['items'][++$i]['value']
                    ];
                }
            }
            $post['items'] = $siDataArr;
        }

        $quote = $this->quoteFactory->create()->load($post['quoteId']);
        $quoteId = $post['quoteId'];
        $items = $post['items'];
        $uploadToQuoteRequest = [
            'action' => $post['action'],
            'items' => $items
        ];
        $rateResponse = $this->fxoRateQuote->getFXORateQuote($quote, null, false, $uploadToQuoteRequest);
        if (!empty($rateResponse['output']) && !empty($rateResponse['output']['alerts'])) {
            foreach ($rateResponse['output']['alerts'] as $alerts) {
                if ($alerts['code'] == 'QCXS.SERVICE.ZERODOLLARSKU') {
                    $itemIds = $this->getItemIds($items);
                    $uploadToQuoteActionQueue = [
                        'action' => $post['action'],
                        'requestedDateTime' => $todayDate,
                        'quoteId' => $quoteId,
                        'items' =>  $items,
                        'itemIds' => $itemIds,
                        'changeRequestedDate' => $post['changeRequestedDate'],
                        'changeRequestedTime' => $post['changeRequestedTime'],
                        'rateQuoteResponse' => $rateResponse['output']
                    ];
                    $arrQueue = $this->makeQueue($uploadToQuoteActionQueue);
                    $resoponse = [
                        "status" => 200,
                        'message' => self::SUCCESS_QUEUE,
                        'Queue' => $arrQueue,
                        'requestChangeProgessBarMsg' => 'You requested a change on '
                        .$this->adminConfigHelper->getFormattedDate($post['changeRequestedDate'], 'd/m/y').' at '
                        .$post['changeRequestedTime'].'.'
                    ];
                }
            }
        }

        return $resoponse;
    }

    /**
     * Get item ids
     *
     * @param array $items
     * @return string
     */
    public function getItemIds($items)
    {
        $itemIds = [];
        foreach ($items as $item) {
            $itemIds[] = $item['item_id'];
        }

        return implode(",", $itemIds);
    }

    /**
     * Make combined array of queue
     *
     * @param array $uploadToQuoteActionQueue
     * @return array
     */
    public function makeQueue($uploadToQuoteActionQueue)
    {
        $arrQueue = $this->customerSession->getUploadToQuoteActionQueue();
        $arrQueue[] = $uploadToQuoteActionQueue;
        $this->customerSession->setUploadToQuoteActionQueue($arrQueue);

        return $arrQueue;
    }

    /**
     * Process upload to quote action queue
     *
     * @return array
     */
    public function processQueue()
    {
        $response = [
            "status" => 200,
            "isQueueStop" => true,
            'message' => self::PROCESS_QUEUE_SUCCESS
        ];
        $arrQueues = $this->customerSession->getUploadToQuoteActionQueue();
        if ($arrQueues) {
            $remainingQueue = $arrQueues;
            foreach ($arrQueues as $key => $arrQueue) {
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                $currDateTime = strtotime($todayDate);
                $requestedDateTime = strtotime($arrQueue['requestedDateTime']);
                $timeDiff = $currDateTime - $requestedDateTime;
                if ($timeDiff >= self::GRACE_PERIOD && $arrQueue['action'] == self::DECLINED) {
                    $this->processDeclinedQueue($arrQueue);
                    unset($remainingQueue[$key]);
                } elseif ($timeDiff >= self::GRACE_PERIOD && $arrQueue['action'] == self::DELETE_ITEM) {
                    $this->processDeletedItemQueue($arrQueue);
                    unset($remainingQueue[$key]);
                } elseif ($timeDiff >= self::GRACE_PERIOD && $arrQueue['action'] == self::CHANGE_REQUESTED) {
                    $this->processChangeRequestedQueue($arrQueue);
                    unset($remainingQueue[$key]);
                }
            }
            if ($remainingQueue) {
                $this->customerSession->setUploadToQuoteActionQueue($remainingQueue);
                $response = [
                    "status" => 200,
                    "isQueueStop" => false,
                    'message' => self::PROCESS_QUEUE_SUCCESS
                ];
            } else {
                $this->customerSession->unsUploadToQuoteActionQueue();
            }
        }

        return $response;
    }

    /**
     * Process declined queue
     *
     * @param array $arrQueue
     * @return void
     */
    public function processDeclinedQueue($arrQueue)
    {
        $this->negotiableQuoteManagement->updateNegotiableSnapShot($arrQueue['quoteId']);
        $this->adminConfigHelper->updateStatusLog($arrQueue['quoteId']);
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' updated history log for quote id : '.$arrQueue['quoteId']
        );
        $this->adminConfigHelper
        ->updateQuoteStatusByKey($arrQueue['quoteId'], NegotiableQuoteInterface::STATUS_DECLINED);
        $values = [];
        $value['quoteStatus'] = self::DECLINED;
        $value['declinedDate'] = $arrQueue['declinedDate'];
        $value['declinedTime'] = $arrQueue['declinedTime'];
        $value['reasonForDeclining'] = $arrQueue['reasonForDeclining'];
        $value['additionalComments'] = $arrQueue['additionalComments'];
        $values[] = $value;
        $this->adminConfigHelper->addCustomLog($arrQueue['quoteId'], $values);
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' updated history custom log for quote id : '.$arrQueue['quoteId']
        );
        $quoteNote = "Quote declined due to - ". $value['reasonForDeclining'];
        $quoteNote .= !empty($value['additionalComments']) ? " - ". $value['additionalComments'] : "";
        $this->graphqlApiHelper->setQuoteNotes($quoteNote, $arrQueue['quoteId'], "quote_declined");
        $this->logger->info(
            __METHOD__ . ':' . __LINE__ . ' Quote notes ['.$quoteNote.'] added for quote id : '.$arrQueue['quoteId']
        );
        $quoteData=[
            'quote_id' => $arrQueue['quoteId'],
            'status' => NegotiableQuoteInterface::STATUS_DECLINED
        ];
        $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
    }

    /**
     * Process deleted item queue
     *
     * @param array $arrQueue
     * @return void
     */
    public function processDeletedItemQueue($arrQueue)
    {
        $this->graphqlApiHelper
            ->setQuoteNotes("Customer deleted quote item " . $arrQueue['itemId'], $arrQueue['quoteId'], "quote_updated");
        $this->adminConfigHelper->removeQuoteItem($arrQueue['quoteId'], $arrQueue['itemId']);
    }

    /**
     * Process change requested queue
     *
     * @param array $arrQueue
     * @return void
     */
    public function processChangeRequestedQueue($arrQueue)
    {
        $quoteId = $arrQueue['quoteId'];
        $quote = $this->quoteFactory->create()->load($quoteId);
        $items = $quote->getAllVisibleItems();
        $itemIdsArr = [];
        $itemSiArr = [];
        foreach ($arrQueue['items'] as $val) {
            $itemIdsArr[] = $val['item_id'];
            $itemSiArr[$val['item_id']] = $val['si'];
        }
        $isChnageRquestedSaved = $this->saveChangeRequestedItem($items, $itemIdsArr, $itemSiArr);
        $this->updateLineItemsSkuDetails($arrQueue['rateQuoteResponse'], $itemIdsArr, $quote);
        if ($isChnageRquestedSaved) {
            $this->adminConfigHelper->updateQuoteStatusByKey(
                $quoteId,
                NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER
            );
            $values = [];
            $value['quoteStatus'] = NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER;
            $value['changeRequestedDate'] = $arrQueue['changeRequestedDate'];
            $value['changeRequestedTime'] = $arrQueue['changeRequestedTime'];
            $values[] = $value;
            $this->adminConfigHelper->updateStatusLog($arrQueue['quoteId']);
            $this->adminConfigHelper->addCustomLog($arrQueue['quoteId'], $values);
            $this->graphqlApiHelper->setQuoteNotes("Requested a few more changes", $quoteId, "quote_change_requested");
            $quoteData=[
                'quote_id' => $quoteId,
                'status' => AdminConfigHelper::CHANGE_REQUEST
            ];
            $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
        }
    }

    /**
     * Save change requested Item
     *
     * @param array $items
     * @param array $itemIdsArr
     * @param array $itemSiArr
     * @return boolean
     */
    public function saveChangeRequestedItem($items, $itemIdsArr, $itemSiArr)
    {
        $isRequestedItemSaved = false;
        foreach ($items as $item) {
            if (in_array($item->getItemId(), $itemIdsArr)) {
                $additionalOption = $item->getOptionByCode('info_buyRequest');
                if (!empty($additionalOption->getOptionId())) {
                    $additionalOptions = $additionalOption->getValue();
                    $productData = (array)$this->serializer->unserialize($additionalOptions);
                    $productData['external_prod'][0]['priceable'] = false;
                    $properties = $productData['external_prod'][0]['properties'] ?? [];
                    foreach ($properties as $k => $prop) {
                        if ($prop['name'] == 'USER_SPECIAL_INSTRUCTIONS') {
                            $productData['external_prod'][0]['properties'][$k]['value']
                            = $itemSiArr[$item->getItemId()];
                        }
                    }
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__ . ' Product Json after Request Change : '.json_encode($productData)
                    );
                    $additionalOption->setValue($this->serializer->serialize($productData))->save();
                    $isRequestedItemSaved = true;
                }
            }
        }

        return $isRequestedItemSaved;
    }

    /**
     * Process upload to quote action queue
     *
     * @param string $undoAction
     * @param int $quoteId
     * @param int $itemId
     * @param string $changeRequestedItemIds
     * @return array
     */
    public function undoActionQueue($undoAction, $quoteId, $itemId, $changeRequestedItemIds)
    {
        $response = [
            "status" => 200,
            "undoAction" => true,
            'message' => 'Undo is success'
        ];
        $arrQueues = $this->customerSession->getUploadToQuoteActionQueue() ?? [];
        $remainingQueue = $arrQueues;
        foreach ($arrQueues as $key => $arrQueue) {
            $declinedCondition = ($arrQueue['quoteId'] == $quoteId && $arrQueue['action'] == self::DECLINED);

            $deleteItemCondition = ($arrQueue['quoteId'] == $quoteId
            && $arrQueue['action'] == self::DELETE_ITEM
            && isset($arrQueue['itemId']) && $arrQueue['itemId'] == $itemId);
            
            $changeRequestedCondition = ($arrQueue['quoteId'] == $quoteId
            && $arrQueue['action'] == self::CHANGE_REQUESTED
            && isset($arrQueue['itemIds']) && $arrQueue['itemIds'] == $changeRequestedItemIds);

            if ($declinedCondition || $deleteItemCondition || $changeRequestedCondition) {
                unset($remainingQueue[$key]);
            }
        }
        if ($remainingQueue) {
            $this->customerSession->setUploadToQuoteActionQueue($remainingQueue);
        } else {
            $this->customerSession->unsUploadToQuoteActionQueue();
        }

        return $response;
    }

    /**
     * Update sku details after 10 sec when request changed
     *
     * @param array $rateQuoteResponse
     * @param array $itemIds
     * @param object $quote
     */
    public function updateLineItemsSkuDetails($rateQuoteResponse, $itemIds, $quote)
    {
        $productLines = $rateQuoteResponse['rateQuote']['rateQuoteDetails'][0]['productLines'] ?? [];
        if (!empty($productLines)) {
            foreach ($productLines as $prodLineData) {
                if (in_array($prodLineData['instanceId'], $itemIds)) {
                    $item = $quote->getItemById($prodLineData['instanceId']);
                    if (!$item) {
                        continue;
                    }
                    $additionalOption = $item->getOptionByCode('info_buyRequest');
                    $additionalOptions = $additionalOption->getValue();
                    $productData = (array)$this->serializer->unserialize($additionalOptions);
                    $productData['productRateTotal'][0] = $prodLineData;
                    $encodedData = $this->serializer->serialize($productData);
                    $additionalOption->setValue($encodedData)->save();
                }
            }
        }
    }

    /**
     * Decline quote by id
     *
     * @param int $quoteId
     * @return void
     */
    public function updateQuoteStatusByKey($quoteId)
    {   
        $quoteStatus = NegotiableQuoteInterface::STATUS_DECLINED;
        if ($this->adminConfigHelper->updateQuoteStatusWithDeclined($quoteId, $quoteStatus)) {
            $this->graphqlApiHelper->setQuoteNotes(
                "Quote decined due to customer has approved the quote and then cleard/deleted all quote (cart) items",
                $quoteId,
                "quote_declined"
            );
        }
    }
}
