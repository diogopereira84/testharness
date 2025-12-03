<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\IntegrationNote\Command;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

interface DeleteByIdInterface
{
    /**
     * Delete quote integration note data by given $cartIntegrationNoteId
     *
     * @param int $cartIntegrationNoteId
     * @return void
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function execute(int $cartIntegrationNoteId): void;
}
