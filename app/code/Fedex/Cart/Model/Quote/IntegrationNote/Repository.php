<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\IntegrationNote;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\DeleteByIdInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\SaveInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetByParentIdInterface;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetListInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

class Repository implements CartIntegrationNoteRepositoryInterface
{
    /**
     * Repository constructor
     *
     * @param DeleteByIdInterface $commandDeleteById
     * @param GetInterface $commandGet
     * @param GetListInterface $commandGetList
     * @param SaveInterface $commandSave
     */
    public function __construct(
        private DeleteByIdInterface $commandDeleteById,
        private GetInterface $commandGet,
        private GetListInterface $commandGetList,
        private SaveInterface $commandSave,
        private readonly GetByParentIdInterface $commandGetByParentId
    )
    {
    }

    /**
     * @inheritdoc
     */
    public function save(CartIntegrationNoteInterface $cartIntegrationNote): int
    {
        return $this->commandSave->execute($cartIntegrationNote);
    }

    /**
     * @inheritdoc
     */
    public function get(int $cartIntegrationNoteId): CartIntegrationNoteInterface
    {
        return $this->commandGet->execute($cartIntegrationNoteId);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        return $this->commandGetList->execute($searchCriteria);
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $cartIntegrationNoteId): void
    {
        $this->commandDeleteById->execute($cartIntegrationNoteId);
    }

    /**
     * @inheritdoc
     */
    public function getByParentId(int $cartIntegrationNoteParentId): CartIntegrationNoteInterface
    {
        return $this->commandGetByParentId->execute($cartIntegrationNoteParentId);
    }
}
