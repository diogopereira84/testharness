<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\OrderService;

class OrderServicePlugin
{
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
     * @param OrderService $subject
     * @param OrderInterface $order
     * @return array
     */
    public function beforePlace(OrderService $subject, OrderInterface $order): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('parent_id', $order->getQuoteId())->create();
        $notes = $this->integrationNoteRepository->getList($searchCriteria);

        foreach ($notes->getItems() as $note) {
            if ($note->getNote() !== null) {
                $noteArr = json_decode($note->getNote(), true);
                if (isset($noteArr['text'])) {
                    $order->addCommentToStatusHistory($noteArr['text']);
                } else {
                    foreach ($noteArr as $noteData) {
                        if (isset($noteData['text'])) {
                            $order->addCommentToStatusHistory($noteData['text']);
                        }
                    }
                }
            }
        }

        return [$order];
    }
}
