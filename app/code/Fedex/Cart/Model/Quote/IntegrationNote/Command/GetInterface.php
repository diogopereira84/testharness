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

interface GetInterface
{
    /**
     * Get quote integration note data by given $cartIntegrationNoteId
     *
     * @param int $cartIntegrationNoteId
     * @return CartIntegrationNoteInterface
     * @throws NoSuchEntityException
     */
    public function execute(int $cartIntegrationNoteId): CartIntegrationNoteInterface;
}
