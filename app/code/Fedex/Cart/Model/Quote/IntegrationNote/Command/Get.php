<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\IntegrationNote\Command;

use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;

class Get implements GetInterface
{
    /**
     * Get constructor
     *
     * @param IntegrationNoteResourceModel $integrationNoteResource
     * @param CartIntegrationNoteInterfaceFactory $cartIntegrationNoteFactory
     */
    public function __construct(
        private IntegrationNoteResourceModel $integrationNoteResource,
        private CartIntegrationNoteInterfaceFactory $cartIntegrationNoteFactory
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function execute(int $cartIntegrationNoteId): CartIntegrationNoteInterface
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

        return $quoteIntegrationNote;
    }
}
