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
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Magento\Framework\Exception\CouldNotSaveException;

class Save implements SaveInterface
{
    /**
     * Save constructor
     *
     * @param IntegrationNoteResourceModel $integrationNoteResource
     */
    public function __construct(
        private IntegrationNoteResourceModel $integrationNoteResource
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function execute(CartIntegrationNoteInterface $quoteIntegrationNote): int
    {
        try {
            /** @var IntegrationNote $quoteIntegrationNote */
            $this->integrationNoteResource->save($quoteIntegrationNote);
            return (int) $quoteIntegrationNote->getId();
        } catch (Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()), $e);
        }
    }
}
