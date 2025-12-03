<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Api;

use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;

interface CartIntegrationNoteRepositoryInterface
{
    /**
     * Save cart integration note data
     *
     * @param CartIntegrationNoteInterface $cartIntegrationNote
     * @return int
     * @throws ValidationException
     * @throws CouldNotSaveException
     */
    public function save(CartIntegrationNoteInterface $cartIntegrationNote): int;

    /**
     * Get cart integration note data by given cartIntegrationNoteId
     *
     * @param int $cartIntegrationNoteId
     * @return CartIntegrationNoteInterface
     * @throws NoSuchEntityException
     */
    public function get(int $cartIntegrationNoteId): CartIntegrationNoteInterface;

    /**
     * Get list of notes
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete the cart integration note data by cartIntegrationNoteId.
     *
     * @param int $cartIntegrationNoteId
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $cartIntegrationNoteId): void;

    /**
     * Get cart integration note data by given cartIntegrationNoteId
     *
     * @param int $cartIntegrationNoteParentId
     * @return CartIntegrationNoteInterface
     * @throws NoSuchEntityException
     */
    public function getByParentId(int $cartIntegrationNoteParentId): CartIntegrationNoteInterface;
}
