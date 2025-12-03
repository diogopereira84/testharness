<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class IntegrationNoteBuilder
{
    /**
     * Max text note length allowed by Fujitsu
     */
    private const MAX_NOTE_LENGTH = 2000;

    /**
     * @param CartIntegrationNoteRepositoryInterface $integrationNoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        protected CartIntegrationNoteRepositoryInterface $integrationNoteRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
    }

    /**
     * @param int $quoteId
     * @return array
     */
    public function build(int $quoteId): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('parent_id', $quoteId)->create();
        $notes = $this->integrationNoteRepository->getList($searchCriteria);
        $formattedNotes = [];

        foreach ($notes->getItems() as $note) {
            if ($note->getNote() !== null) {
                $noteArr = json_decode($note->getNote(), true);
                if (isset($noteArr['text'])) {
                    $noteArr['text'] = $this->formatNoteText($noteArr['text']);
                    $formattedNotes[] = $noteArr;
                } else {
                    foreach ($noteArr as $noteData) {
                        $noteData['text'] = $this->formatNoteText($noteData['text']);
                        $formattedNotes[] = $noteData;
                    }
                }
            }
        }

        return $formattedNotes;
    }

    /**
     * Format note text
     *
     * @param string $note
     * @return string
     */
    private function formatNoteText(string $note): string
    {
        return strlen($note) > self::MAX_NOTE_LENGTH
            ? substr($note, 0, self::MAX_NOTE_LENGTH)
            : $note;
    }
}
