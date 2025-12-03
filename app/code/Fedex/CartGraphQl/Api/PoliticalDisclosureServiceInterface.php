<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2025 Fedex
 * @author       Athira Indrakumar <athiraindrakumar.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Api;

use Magento\Framework\Exception\CouldNotSaveException;

interface PoliticalDisclosureServiceInterface
{
    /**
     * Save political disclosure details for an order.
     *
     * @param array $disclosureInput
     * @param string $reserveId
     * @return bool|int
     * @throws CouldNotSaveException
     */
    public function setDisclosureDetails(array $disclosureInput, string $reserveId): bool|int;
}
