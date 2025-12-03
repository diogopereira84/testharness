<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\IntegrationNote\Command;

use Exception;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class DeleteById implements DeleteByIdInterface
{
    /**
     * DeleteById constructor
     *
     * @param IntegrationNoteResourceModel $integrationNoteResource
     * @param CartIntegrationNoteInterfaceFactory $cartIntegrationNoteFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private IntegrationNoteResourceModel $integrationNoteResource,
        private CartIntegrationNoteInterfaceFactory $cartIntegrationNoteFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function execute(int $cartIntegrationNoteId): void
    {
        /** @var IntegrationNote $quoteIntegrationNote */
        $quoteIntegrationNote = $this->cartIntegrationNoteFactory->create();
        $this->integrationNoteResource->load(
            $quoteIntegrationNote,
            $cartIntegrationNoteId,
            CartIntegrationNoteInterface::QUOTE_INTEGRATION_NOTE_ID
        );

        if (null === $quoteIntegrationNote->getId()) {
            throw new NoSuchEntityException(
                __(
                    'Quote integration note with id "%value" does not exist.',
                    ['value' => $cartIntegrationNoteId]
                )
            );
        }

        try {
            $this->integrationNoteResource->delete($quoteIntegrationNote);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete quote integration note.'), $e);
        }
    }
}
