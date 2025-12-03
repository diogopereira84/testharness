<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\B2b\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\ItemNote\AddItemNote;
use Magento\NegotiableQuote\Model\ItemNote\MoveItemNotesToClonedItems;
use Magento\NegotiableQuote\Model\NegotiableQuote\HasItemWithNegotiablePrice;
use Magento\NegotiableQuote\Model\NegotiableQuoteConverter;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\NegotiableQuote\Model\QuoteUpdater;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem as NegotiableQuoteItemResource;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\NegotiableQuote\Model\Email\Sender;
use Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Class for managing negotiable quotes.
 */
class NegotiableQuoteManagement extends \Magento\NegotiableQuote\Model\NegotiableQuoteManagement
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\NegotiableQuote\Model\CommentManagementInterface
     */
    private $commentManagement;

    /**
     * @var \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface
     */
    private $quoteItemManagement;

    /**
     * @var \Magento\NegotiableQuote\Model\NegotiableQuoteConverter
     */
    private $negotiableQuoteConverter;

    /**
     * @var \Magento\NegotiableQuote\Model\QuoteUpdater
     */
    private $quoteUpdater;

    /**
     * @var \Magento\NegotiableQuote\Model\Quote\History
     */
    private $quoteHistory;

    /**
     * @var \Magento\NegotiableQuote\Model\Validator\ValidatorInterfaceFactory
     */
    private $validatorFactory;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CommentManagementInterface $commentManagement
     * @param NegotiableQuoteItemManagementInterface $quoteItemManagement
     * @param NegotiableQuoteConverter $negotiableQuoteConverter
     * @param QuoteUpdater $quoteUpdater
     * @param History $quoteHistory
     * @param ValidatorInterfaceFactory $validatorFactory
     * @param SessionManagerInterface $sessionManagerInterface
     * @param LoggerInterface $logger
     * @param NegotiableQuoteItemResource $negotiableQuoteItemResource
     * @param AddItemNote $addItemNote
     * @param HasItemWithNegotiablePrice $hasItemWithNegotiablePrice
     * @param MoveItemNotesToClonedItems $moveItemNotesToClonedItems
     * @param Sender $emailSender
     * @param ToggleConfig toggleConfig
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        \Magento\NegotiableQuote\Model\CommentManagementInterface $commentManagement,
        \Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface $quoteItemManagement,
        \Magento\NegotiableQuote\Model\NegotiableQuoteConverter $negotiableQuoteConverter,
        \Magento\NegotiableQuote\Model\QuoteUpdater $quoteUpdater,
        \Magento\NegotiableQuote\Model\Quote\History $quoteHistory,
        ValidatorInterfaceFactory $validatorFactory,
        protected \Magento\Framework\Session\SessionManagerInterface $sessionManagerInterface,
        protected LoggerInterface $logger,
        readonly NegotiableQuoteItemResource $negotiableQuoteItemResource,
        readonly AddItemNote $addItemNote,
        readonly HasItemWithNegotiablePrice $hasItemWithNegotiablePrice,
        readonly MoveItemNotesToClonedItems $moveItemNotesToClonedItems,
        readonly Sender $emailSender,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct(
            $quoteRepository,
            $commentManagement,
            $quoteItemManagement,
            $negotiableQuoteConverter,
            $quoteUpdater,
            $quoteHistory,
            $validatorFactory,
            $negotiableQuoteItemResource,
            $addItemNote,
            $hasItemWithNegotiablePrice,
            $moveItemNotesToClonedItems
        );
        $this->quoteRepository = $quoteRepository;
        $this->commentManagement = $commentManagement;
        $this->quoteItemManagement = $quoteItemManagement;
        $this->negotiableQuoteConverter = $negotiableQuoteConverter;
        $this->quoteUpdater = $quoteUpdater;
        $this->quoteHistory = $quoteHistory;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function close($quoteId, $force = false)
    {
        $quote = $this->getNegotiableQuote($quoteId);

        $validator = $this->validatorFactory->create(['action' => 'close']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ((!$validateResult->hasMessages() || $force)
            && !in_array(
                $quote->getExtensionAttributes()->getNegotiableQuote()->getStatus(),
                [NegotiableQuoteInterface::STATUS_CLOSED, NegotiableQuoteInterface::STATUS_ORDERED]
            )
        ) {
            $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->setStatus(NegotiableQuoteInterface::STATUS_CLOSED);
            $this->quoteHistory->closeLog($quoteId);
            $this->updateSnapshotQuote($quoteId);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function adminSend($quoteId, $commentText = '', array $files = [])
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'send']);
        $validateResult = $validator->validate(['quote' => $quote, 'files' => $files]);
        if ($validateResult->hasMessages()) {
            $exception = new InputException(__('Cannot send a B2B quote.'));
            foreach ($validateResult->getMessages() as $message) {
                $exception->addError($message);
            }
            $errorMsg = $exception->getMessage();
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Quote Id = ' . $quoteId. '. Error Message: ' .
            $errorMsg);
            throw $exception;
        }

        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        $negotiableQuote->setHasUnconfirmedChanges(false)
            ->setIsCustomerPriceChanged(false)
            ->setIsShippingTaxChanged(false);
        $result = $this->save($quoteId, [], NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        $this->commentManagement->update(
            $quoteId,
            $commentText,
            $files
        );
        $this->quoteHistory->updateLog($quoteId, true);
        $quote = $this->getNegotiableQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        if ($negotiableQuote->getNegotiatedPriceValue() !== null) {
            $this->quoteHistory->removeFrontMessage($negotiableQuote);
        }
        $this->updateSnapshotQuote($quoteId);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function updateProcessingByCustomerQuoteStatus($quoteId, $needSave = true)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        $quoteStatus = $negotiableQuote->getStatus();
        $validator = $this->validatorFactory->create(['action' => 'edit']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if (!$validateResult->hasMessages()) {
            if ($quoteStatus !== NegotiableQuoteInterface::STATUS_CREATED) {
                $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER);
                $this->quoteHistory->updateStatusLog($quoteId, false);
                $this->updateSnapshotQuoteStatus($quoteId, NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER);
            }

            if ($needSave) {
                $this->quoteRepository->save($quote);
            }
        }

        return $negotiableQuote->getStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function saveAsDraft($quoteId, array $quoteData, array $commentData = [])
    {
        $this->save($quoteId, $quoteData);

        if (!empty($commentData) && $this->getNegotiableQuote($quoteId)) {
            $this->commentManagement->update(
                $quoteId,
                $commentData['message'] ?? null,
                $this->commentManagement->getFilesNamesList(
                    $commentData['files'] ?? []
                ),
                false,
                true
            );
        }

        return $this;
    }

    /**
     * Save quote id with provided data.
     *
     * @param int $quoteId
     * @param array $data [
     *      'items' => [] array of quote items,
     *      'addItems' => [] add new items to quote,
     *      'configuredSkus' => [] configured products,
     *      'recalcPrice' => bool flag that triggers quote recalculation
     *  ]
     * @param string $status [optional]
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function save($quoteId, array $data, $status = '')
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $this->quoteUpdater->updateQuote($quoteId, $data);
        if ($status) {
            $negotiableQuote = $this->retrieveNegotiableQuote($quote);
            $negotiableQuote->setHasUnconfirmedChanges(false);
            $negotiableQuote->setIsCustomerPriceChanged(false);
            $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN);
        }
        $this->quoteRepository->save($quote);
        if ($status == NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN) {
            $this->emailSender->sendChangeQuoteEmailToBuyer(
                $quote,
                Sender::XML_PATH_BUYER_QUOTE_UPDATED_BY_SELLER_TEMPLATE
            );
        }

        return true;
    }

    /**
     * Updates data of snapshot quote.
     *
     * @param int $quoteId
     * @return $this
     */
    private function updateSnapshotQuote($quoteId)
    {
        $this->sessionManagerInterface->start();
        if (empty($this->sessionManagerInterface->getAdminQuoteView())) { // If admin is viewing quote.
            $quote = $this->quoteRepository->get($quoteId, ['*']);
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
            $negotiableQuote->setSnapshot(json_encode($this->negotiableQuoteConverter->quoteToArray($quote)));
            $this->quoteRepository->save($quote);
        }
        return $this;
    }

    /**
     * Updates status in quote snapshot.
     *
     * @param int $quoteId
     * @param string $status
     * @return $this
     */
    private function updateSnapshotQuoteStatus($quoteId, $status)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $snapshot = json_decode((string)$negotiableQuote->getSnapshot(), true);
        $snapshot['negotiable_quote'][NegotiableQuoteInterface::QUOTE_STATUS] = $status;
        $negotiableQuote->setSnapshot(json_encode($snapshot));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSnapshotQuote($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId, ['*']);
        $quoteExtensionAttributes = $quote->getExtensionAttributes();
        $negotiableQuote = $quoteExtensionAttributes->getNegotiableQuote();
        $snapshot = json_decode((string)$negotiableQuote->getSnapshot(), true);
        if (is_array($snapshot)) {
            $isNegotiableQuoteItem = ($this->toggleConfig->getToggleConfigValue('xmen_D189985_fix') &&
            empty($snapshot['negotiable_quote_item'])) ? true : false;
            if (!$isNegotiableQuoteItem) {
                $quote = $this->negotiableQuoteConverter->arrayToQuote($snapshot);
            }
        }

        return $quote;
    }

    /**
     * {@inheritdoc}
     */
    public function decline($quoteId, $reason)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'decline']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ($validateResult->hasMessages()) {
            $exception = new InputException();
            foreach ($validateResult->getMessages() as $message) {
                $exception->addError($message);
            }
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Quote Id = ' . $quoteId. '. Error Message: ' .
            $exception->getMessage());
            throw $exception;
        }

        $oldData = $this->quoteHistory->collectOldDataFromQuote($quote);
        $quote->getExtensionAttributes()
            ->getNegotiableQuote()
            ->setStatus(NegotiableQuoteInterface::STATUS_DECLINED)
            ->setIsCustomerPriceChanged(false)
            ->setHasUnconfirmedChanges(false)
            ->setIsShippingTaxChanged(false);
        $this->resetCustomPrice($quote);
        $quote->getShippingAddress()
            ->setShippingMethod(null)
            ->setShippingDescription(null);
        if ($quote->getExtensionAttributes()->getShippingAssignments()) {
            foreach ($quote->getExtensionAttributes()->getShippingAssignments() as $shippingAssignment) {
                $shippingAssignment->getShipping()->setMethod(null);
            }
        }
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true);
        $this->commentManagement->update(
            $quoteId,
            $reason,
            [],
            true
        );
        $this->quoteHistory->updateLog($quoteId, true, NegotiableQuoteInterface::STATUS_DECLINED);
        $this->emailSender->sendChangeQuoteEmailToBuyer(
            $quote,
            Sender::XML_PATH_BUYER_QUOTE_DECLINED_BY_SELLER_TEMPLATE,
            $reason
        );
        $this->updateSnapshotQuote($quoteId);
        $this->quoteHistory->checkPricesAndDiscounts($quote, $oldData);
        $this->quoteHistory->removeAdminMessage($quote->getExtensionAttributes()->getNegotiableQuote());

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function order($quoteId)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        $validator = $this->validatorFactory->create(['action' => 'checkout']);
        $validateResult = $validator->validate(['quote' => $quote]);
        if ($validateResult->hasMessages()) {
            return false;
        }

        $quote->getExtensionAttributes()
            ->getNegotiableQuote()
            ->setStatus(NegotiableQuoteInterface::STATUS_ORDERED);
        $this->updateSnapshotQuoteStatus($quoteId, NegotiableQuoteInterface::STATUS_ORDERED);
        $this->quoteRepository->save($quote);
        $this->quoteHistory->updateLog($quoteId);

        return true;
    }

    /**
     * Retrieve negotiable quote from regular quote.
     *
     * @param CartInterface $quote
     * @return NegotiableQuoteInterface|null
     */
    private function retrieveNegotiableQuote(CartInterface $quote)
    {
        $negotiableQuote = null;

        if ($quote->getExtensionAttributes() && $quote->getExtensionAttributes()->getNegotiableQuote()) {
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        }

        return $negotiableQuote;
    }

    /**
     * @inheritdoc
     */
    public function getNegotiableQuote($quoteId)
    {
        $quote = $this->retrieveQuote($quoteId);
        $negotiableQuote = $this->retrieveNegotiableQuote($quote);
        if ($negotiableQuote === null
            || !$negotiableQuote->getIsRegularQuote()
        ) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Requested quote is not found. Row ID: quoteId = ' .
            $quoteId);
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __(
                    'Requested quote is not found. Row ID: %fieldName = %fieldValue',
                    ['fieldName' => 'quoteId', 'fieldValue' => $quoteId]
                )
            );
        }

        return $quote;
    }

    /**
     * {@inheritdoc}
     */
    public function setHasChangesInNegotiableQuote(CartInterface $quote)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $value = $negotiableQuote->getNegotiatedPriceValue();
        if ($value !== null) {
            $negotiableQuote->setHasUnconfirmedChanges(true);
        }
    }

    /**
     * Set customer price changed flag in negotiable quote.
     *
     * @param CartInterface $quote
     * @return void
     * @codeCoverageIgnore
     */
    private function setIsCustomerPriceChanged(CartInterface $quote)
    {
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if ($negotiableQuote->getNegotiatedPriceValue() !== null) {
            $negotiableQuote->setIsCustomerPriceChanged(true);
        }
        $this->quoteRepository->save($quote);
    }

    /**
     * {@inheritdoc}
     */
    private function resetCustomPrice(CartInterface $quote)
    {
        if ($quote->getExtensionAttributes() !== null
            && $quote->getExtensionAttributes()->getNegotiableQuote() !== null) {
            $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->setNegotiatedPriceType(null)
                ->setNegotiatedPriceValue(null)
                ->setShippingPrice(null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeNegotiation($quoteId)
    {
        $quote = $this->quoteRepository->get($quoteId);
        $oldData = $this->quoteHistory->collectOldDataFromQuote($quote);
        $this->resetCustomPrice($quote);
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true, false);
        $this->quoteHistory->checkPricesAndDiscounts($quote, $oldData);
        $this->quoteHistory->updateLog($quoteId, true);
        $this->updateSnapshotQuote($quoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function recalculateQuote($quoteId, $updatePrice = true)
    {
        $quote = $this->quoteRepository->get($quoteId);
        $oldQuoteData = $this->quoteHistory->collectOldDataFromQuote($quote);
        $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, $updatePrice, $updatePrice, false);
        $checkData = $this->quoteHistory->checkPricesAndDiscounts($quote, $oldQuoteData);
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        if (($checkData->getIsTaxChanged() || $checkData->getIsPriceChanged()
                || $checkData->getIsDiscountChanged())
            && $negotiableQuote->getStatus() != NegotiableQuoteInterface::STATUS_CREATED
            && $negotiableQuote->getNegotiatedPriceValue() !== null
        ) {
            $negotiableQuote->setIsCustomerPriceChanged(true);
        }
        $negotiableQuote->setIsAddressDraft(false);
        $this->quoteRepository->save($quote);
    }

    /**
     * {@inheritdoc}
     */
    public function updateQuoteItems($quoteId, array $cartData = [])
    {
        $quote = $this->quoteRepository->get($quoteId);
        if (is_array($cartData)) {
            $oldQuoteData = $this->quoteHistory->collectOldDataFromQuote($quote);

            $this->quoteUpdater->updateQuoteItemsByCartData($quote, $cartData);

            $this->quoteItemManagement->recalculateOriginalPriceTax($quoteId, true, true);
            $result = $this->quoteHistory->checkPricesAndDiscounts($quote, $oldQuoteData);
            if ($result->getIsChanged()
                || $quote->getExtensionAttributes()->getNegotiableQuote()->getIsCustomerPriceChanged()) {
                $this->quoteRepository->save($quote);
            }
        }
    }

    /**
     * Retrieve quote from repository.
     *
     * @param int $quoteId
     * @return CartInterface
     * @throws NoSuchEntityException
     * @codeCoverageIgnore
     */
    private function retrieveQuote($quoteId)
    {
        try {
            return $this->quoteRepository->get($quoteId, ['*']);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Requested quote is not found. Row ID: quoteId = ' .
            $quoteId);
            throw new NoSuchEntityException(
                __(
                    'Requested quote is not found. Row ID: %fieldName = %fieldValue',
                    ['fieldName' => 'quoteId', 'fieldValue' => $quoteId]
                )
            );
        }
    }

    /**
     * Updates data of snapshot quote.
     *
     * @param int $quoteId
     * @return $this
     */
    private function updatedSnapshotQuote($quoteId)
    {
        $this->sessionManagerInterface->start();
        if (empty($this->sessionManagerInterface->getAdminQuoteView())) { // If admin is viewing quote.
            $quote = $this->quoteRepository->get($quoteId, ['*']);
            $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
            $negotiableQuote->setSnapshot(json_encode($this->negotiableQuoteConverter->quoteToArray($quote)));
            $negotiableQuote->save($quote);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function closed($quoteId)
    {
        $quote = $this->getNegotiableQuote($quoteId);
        if (!empty($quote)) {
            $quote->getExtensionAttributes()
                ->getNegotiableQuote()
                ->setStatus(NegotiableQuoteInterface::STATUS_CLOSED);
            $this->quoteHistory->closeLog($quoteId);
            $this->updatedSnapshotQuote($quoteId);

            return true;
        } else {
            //@codeCoverageIgnoreStart
            return false;
            //@codeCoverageIgnoreEnd
        }
    }


    /**
     * Update negotiable quote snapshot
     *
     * @param int $quoteId
     * @return void
     */
    public function updateNegotiableSnapShot($quoteId) {
        $this->updateSnapshotQuote($quoteId);
    }
}
