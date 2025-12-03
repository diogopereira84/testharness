<?php
/**
 * @category    Fedex
 * @package     Fedex_Mars
 * @copyright   Copyright (c) 2024 Fedex
 */

declare(strict_types=1);

namespace Fedex\Mars\Model;

use Exception;
use Fedex\Cart\Api\CartIntegrationItemRepositoryInterface;
use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Mars\Api\QuoteProcessInterface;
use Fedex\Mars\Model\Config;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Api\CommentLocatorInterface;
use Magento\NegotiableQuote\Model\HistoryRepositoryInterface;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Psr\Log\LoggerInterface;

class QuoteProcess implements QuoteProcessInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CartIntegrationRepositoryInterface $integrationRepository
     * @param CartIntegrationNoteRepositoryInterface $integrationNote
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CartIntegrationItemRepositoryInterface $integrationItem
     * @param CommentLocatorInterface $negotiableQuoteComment
     * @param HistoryRepositoryInterface $negotiableQuoteHistory
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        private CartRepositoryInterface $quoteRepository,
        private CartIntegrationRepositoryInterface $integrationRepository,
        private CartIntegrationNoteRepositoryInterface $integrationNote,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private CartIntegrationItemRepositoryInterface $integrationItem,
        private CommentLocatorInterface $negotiableQuoteComment,
        private HistoryRepositoryInterface $negotiableQuoteHistory,
        protected LoggerInterface $logger,
        private Config $config
    ) {
    }

    /**
     * Get quote json
     *
     * @param int $id
     *
     * @return array
     */
    public function getQuoteJson(int $id): array
    {
        $quoteData = [];
        
        try {
            $quote = $this->quoteRepository->get($id);
            $quoteData = $this->getQuoteData($quote);
            
            // D-241246 MARS quote identifier toggle
            if ($this->config->isQuoteIdentifierEnabled()) {
                $quoteData['content_type'] = 'QUOTE';
            }
            
            $negotiableQuoteModel = $quote->getExtensionAttributes()->getNegotiableQuote();
            $negotiableQuote = $negotiableQuoteModel->getData();
            $negotiableQuote ? $hasNegotiableQuote = true : $hasNegotiableQuote = false;
            $quoteData['negotiable_quote'] = $this->getNegotiableQuoteData(
                $negotiableQuoteModel,
                $negotiableQuote,
                $hasNegotiableQuote
            );
            
            $quoteData['quote_payment'] = $this->getSimpleQuoteTableData($quote->getPaymentsCollection());

            $quoteData['quote_item'] = $this->getQuoteItemData($quote->getItemsCollection(), $hasNegotiableQuote);

            $quoteData['quote_address'] = $this->getQuoteAddressData($quote->getAddressesCollection());

            $quoteData['quote_integration_note'] = $this->getQuoteIntegrationNoteData($id);

            $quoteData['quote_integration'] = $this->getQuoteIntegrationData($id);
            
            return [$quoteData];
        } catch (NoSuchEntityException $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .' Unable to find quote id to build MARS upload to quote json. Quote ID = '.
                $id . ' . Error Message: ' . $e->getMessage()
            );
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ .' An error occurred while building MARS upload to quote json. Quote ID = '.
                $id . ' . Error Message: ' . $e->getMessage()
            );
        }

        return $quoteData;
    }

    /**
     * Gets correct quote data
     *
     * @param mixed $quoteCollection
     * @param callable $quoteFilterFn
     * @param int $quoteFilterMode
     * @param bool $needsEncoding
     * @param string $encodedValue
     *
     * @return array
     */
    public function getSimpleQuoteTableData(
        $quoteCollection,
        $quoteFilterFn = 'Fedex\Mars\Model\QuoteProcess::checkIsNull',
        $quoteFilterMode = 0,
        $needsEncoding = false,
        $encodedValue = ''
    ) {

        $quoteCollectionData = [];

        $mazeGeeksB2743693Enabled = $this->config->isMazeGeeksB2743693Enabled();

        foreach ($quoteCollection->getItems() as $item) {
            if ($mazeGeeksB2743693Enabled) {
                $quoteItemData = $item->getData();
            } else {
                $quoteItemData = array_filter($item->getData(), $quoteFilterFn, $quoteFilterMode);
            }
            if ($needsEncoding) {
                if (isset($quoteItemData[$encodedValue])) {
                    $quoteItemData[$encodedValue] = json_encode($quoteItemData[$encodedValue]);
                }
            }
            $quoteCollectionData[] = $quoteItemData;
        }

        return $quoteCollectionData;
    }

    /**
     * Gets correct quote data from quote table
     *
     * @param CartRepositoryInterface $quote
     *
     * @return array
     */
    public function getQuoteData($quote)
    {
        return array_filter(
            $quote->getData(),
            'Fedex\Mars\Model\QuoteProcess::quoteFilter',
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Gets correct data from negotiable_quote table
     *
     * @param NegotiableQuote $negotiableQuoteModel
     * @param array $negotiableQuote
     * @param boolean $hasNegotiableQuote
     * @param callable $quoteFilterFn
     *
     * @return array
     */
    public function getNegotiableQuoteData(
        $negotiableQuoteModel,
        $negotiableQuote,
        $hasNegotiableQuote,
        $quoteFilterFn = 'Fedex\Mars\Model\QuoteProcess::checkIsNull'
    ) {
        
        $negotiableQuoteArr = [];

        $mazeGeeksB2743693Enabled = $this->config->isMazeGeeksB2743693Enabled();

        if ($hasNegotiableQuote) {
            if ($mazeGeeksB2743693Enabled) {
                $negotiableQuoteArr = $negotiableQuote;
            } else {
                $negotiableQuoteArr = array_filter($negotiableQuote, $quoteFilterFn);
            }
         
            $negotiableQuoteCommentArr = [];
            $negotiableQuoteComment = $this->negotiableQuoteComment->getListForQuote(
                $negotiableQuoteModel->getQuoteId()
            );
            foreach ($negotiableQuoteComment as $commentItem) {

                if ($mazeGeeksB2743693Enabled) {
                    $negotiableQuoteCommentItem = $commentItem->getData();
                } else {
                    $negotiableQuoteCommentItem = array_filter(
                        $commentItem->getData(),
                        'Fedex\Mars\Model\QuoteProcess::negotiableQuoteCommentFilter',
                        ARRAY_FILTER_USE_BOTH
                    );
                }
                $negotiableQuoteCommentArr[] = $negotiableQuoteCommentItem;
            }
            $negotiableQuoteArr['negotiable_quote_comment'] = $negotiableQuoteCommentArr;
    
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('quote_id', $negotiableQuoteModel->getQuoteId())
                ->create();
            $negotiableQuoteHistory = $this->negotiableQuoteHistory->getList($searchCriteria);
            $negotiableQuoteArr['negotiable_quote_history'] = $this->getSimpleQuoteTableData($negotiableQuoteHistory);
        }

        return $negotiableQuoteArr;
    }

    /**
     * Gets correct data from quote_item table
     *
     * @param Item $quoteItems
     * @param boolean $hasNegotiableQuote
     * @param callable $quoteFilterFn
     *
     * @return array
     */
    public function getQuoteItemData(
        $quoteItems,
        $hasNegotiableQuote,
        $quoteFilterFn = 'Fedex\Mars\Model\QuoteProcess::checkIsNull'
    ) {
    
        $quoteItemArr = [];

        $mazeGeeksB2743693Enabled = $this->config->isMazeGeeksB2743693Enabled();

        foreach ($quoteItems->getItems() as $quoteItem) {

            if ($mazeGeeksB2743693Enabled) {
                $quoteItemData = $quoteItem->getData();
            } else {
                $quoteItemData = array_filter(
                    $quoteItem->getData(),
                    'Fedex\Mars\Model\QuoteProcess::quoteItemFilter',
                    ARRAY_FILTER_USE_BOTH
                );
            }

            $quoteItemOptionsArr = [];
            foreach ($quoteItem->getOptions() as $quoteItemOptions) {
                $quoteItemOptionsArr[] = $mazeGeeksB2743693Enabled
                ? $quoteItemOptions->getData()
                : array_filter($quoteItemOptions->getData(), $quoteFilterFn);
            }
            $quoteItemData['quote_item_option'] = $quoteItemOptionsArr;

            $quoteItemData['quote_address_item'] = $mazeGeeksB2743693Enabled
                ? $quoteItem->getAddress()->getData()
                : array_filter(
                    $quoteItem->getAddress()->getData(),
                    'Fedex\Mars\Model\QuoteProcess::quoteAddressItemFilter',
                    ARRAY_FILTER_USE_BOTH
                );

            $quoteIntegrationItemArr = [];
            try {
                $quoteIntegrationItemModel = $this->integrationItem->getByQuoteItemId((int)$quoteItem->getItemId());
                $quoteIntegrationItemArr[] = $mazeGeeksB2743693Enabled
                    ? $quoteIntegrationItemModel->getData()
                    : array_filter($quoteIntegrationItemModel->getData(), $quoteFilterFn);
            } catch (NoSuchEntityException $e) {
                $this->logger->critical(
                    $e->getMessage()
                );
            }
            $quoteItemData['quote_integration_item'] = $quoteIntegrationItemArr;
            
            $negotiableQuoteItemArr = [];
            if ($hasNegotiableQuote) {
                $negotiableQuoteItem = $quoteItem->getExtensionAttributes()->getNegotiableQuoteItem()->getData();
                $negotiableQuoteItemArr[] = $mazeGeeksB2743693Enabled
                    ? $negotiableQuoteItem
                    : array_filter($negotiableQuoteItem, $quoteFilterFn);
            }
            $quoteItemData['negotiable_quote_item'] = $negotiableQuoteItemArr;

            $quoteItemArr[] = $quoteItemData;
        }

        return $quoteItemArr;
    }

    /**
     * Gets correct data from quote_address table
     *
     * @param Address $quoteAddress
     * @param callable $quoteFilterFn
     *
     * @return array
     */
    public function getQuoteAddressData(
        $quoteAddress,
        $quoteFilterFn = 'Fedex\Mars\Model\QuoteProcess::quoteAddressFilter'
    ) {

        $quoteAddressArr = [];

        $mazeGeeksB2743693Enabled = $this->config->isMazeGeeksB2743693Enabled();

        foreach ($quoteAddress->getItems() as $quoteAddressItem) {
            if ($mazeGeeksB2743693Enabled) {
                $quoteAddressItemData = $quoteAddressItem->getData();
            } else {
                $quoteAddressItemData = array_filter($quoteAddressItem->getData(), $quoteFilterFn);
            }
            $quoteAddressItemData['quote_shipping_rate'] = $this->getSimpleQuoteTableData(
                $quoteAddressItem->getShippingRatesCollection()
            );
            
            $quoteAddressArr[] = $quoteAddressItemData;
        }

        return $quoteAddressArr;
    }

    /**
     * Gets correct data from quote_integration_note table
     *
     * @param int $quoteId
     * @param callable $quoteFilterFn
     *
     * @return array
     */
    public function getQuoteIntegrationNoteData(
        $quoteId,
        $quoteFilterFn = 'Fedex\Mars\Model\QuoteProcess::checkIsNull'
    ) {
        $mazeGeeksB2743693Enabled = $this->config->isMazeGeeksB2743693Enabled();

        $searchCriteria = $this->searchCriteriaBuilder->addFilter('parent_id', $quoteId)->create();
        $quoteIntegrationNotes = $this->integrationNote->getList($searchCriteria)->getItems();
        $quoteIntegrationNoteArr = [];

        if ($quoteIntegrationNotes) {
            foreach ($quoteIntegrationNotes as $quoteIntegrationNote) {
                if ($mazeGeeksB2743693Enabled) {
                    $integrationNote = $quoteIntegrationNote->getData();
                } else {
                    $integrationNote = array_filter($quoteIntegrationNote->getData(), $quoteFilterFn);
                }
                $quoteIntegrationNoteArr[] = $integrationNote;
            }
        }

        return $quoteIntegrationNoteArr;
    }

    /**
     * Gets correct data from quote_integration table
     *
     * @param int $quoteId
     * @param callable $quoteFilterFn
     *
     * @return array
     */
    public function getQuoteIntegrationData(
        $quoteId,
        $quoteFilterFn = 'Fedex\Mars\Model\QuoteProcess::checkIsNull'
    ) {
        $quoteIntegrationArr = [];

        $mazeGeeksB2743693Enabled = $this->config->isMazeGeeksB2743693Enabled();
        
        try {
            $quoteIntegrationModel = $this->integrationRepository->getByQuoteId($quoteId);
            if ($mazeGeeksB2743693Enabled) {
                $quoteIntegration = $quoteIntegrationModel->getData();
            } else {
                $quoteIntegration = array_filter($quoteIntegrationModel->getData(), $quoteFilterFn);
            }
            $quoteIntegrationArr[] = $quoteIntegration;

        } catch (NoSuchEntityException $e) {
            $this->logger->critical(
                $e->getMessage()
            );
        }

        return $quoteIntegrationArr;
    }

    /**
     * Array filter function that will return required quote data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function quoteFilter($v, $k)
    {
        return $v !== null && $k != 'items' && $k != 'extension_attributes' &&
            $k != 'totals_collected_flag';
    }

    /**
     * Array filter function that will omit null values from array
     *
     * @codeCoverageIgnore
     *
     * @param mixed $entry
     *
     * @return bool
     */
    protected function checkIsNull($entry)
    {
        return $entry !== null;
    }

    /**
     * Array filter function that will return required negotiable quote comment data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function negotiableQuoteCommentFilter($v, $k)
    {
        return $v !== null && $k != 'attachments';
    }

    /**
     * Array filter function that will return required quote item data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function quoteItemFilter($v, $k)
    {
        return $v !== null && $k != 'extension_attributes' && $k != 'qty_options'
            && $k != 'product' && $k != 'tax_class_id' && $k != 'has_error';
    }

    /**
     * Array filter function that will return required quote address item data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function quoteAddressItemFilter($v, $k)
    {
        return $v !== null && $k != 'reward_points_balance' && $k != 'base_reward_currency_amount'
            && $k != 'reward_currency_amount' && $k != 'ext_no' && $k != 'entity_id';
    }

    /**
     * Array filter function that will return required quote address data
     *
     * @codeCoverageIgnore
     *
     * @param mixed $v
     * @param mixed $k
     *
     * @return bool
     */
    protected function quoteAddressFilter($v, $k)
    {
        return $v !== null && $k != 'entity_id';
    }
}
