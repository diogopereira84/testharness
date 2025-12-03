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
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;

interface SaveInterface
{
    /**
     * Save quote integration note data
     *
     * @param CartIntegrationNoteInterface $quoteIntegrationNote
     * @return int
     * @throws ValidationException
     * @throws CouldNotSaveException
     */
    public function execute(CartIntegrationNoteInterface $quoteIntegrationNote): int;
}
