<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Note;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class OrderNotes
{
    /**
     * @param CartIntegrationNoteRepositoryInterface $cartIntegrationNoteRepository
     */
    public function __construct(
        private readonly CartIntegrationNoteRepositoryInterface $cartIntegrationNoteRepository
    ) {
    }

    /**
     * @param string|null $notes
     * @param int $cartId
     * @return false|string|null
     * @throws NoSuchEntityException
     */
    public function getCurrentNotes($notes, $cartId): false|string|null
    {
        $notes = $notes ?? null;
        if (!$notes && empty($notes)) {
            $cartIntegrationNote = $this->cartIntegrationNoteRepository->getByParentId((int)$cartId);
            $notes = $this->getNotes($cartIntegrationNote);
        }
        return $notes;
    }

    /**
     * @param $cartIntegrationNote
     * @return mixed|null
     */
    public function getNotes($cartIntegrationNote): mixed
    {
        $notes = null;
        if ($cartIntegrationNote->getId()) {
            $notes = $cartIntegrationNote->getNote();
        }
        return $notes;
    }
}
