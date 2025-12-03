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
use Magento\Framework\Exception\NoSuchEntityException;

interface GetByParentIdInterface
{
    /**
     * Get quote integration note data by given $cartIntegrationNoteId
     *
     * @param int $cartIntegrationNoteParentId
     * @return CartIntegrationNoteInterface
     * @throws NoSuchEntityException
     */
    public function execute(int $cartIntegrationNoteParentId): CartIntegrationNoteInterface;
}
