<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Note\Command;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class Save implements SaveInterface
{
    /**
     * Save constructor
     *
     * @param CartIntegrationNoteRepositoryInterface $cartIntegrationNoteRepository
     * @param CartIntegrationNoteInterfaceFactory $cartIntegrationNoteFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CartIntegrationNoteRepositoryInterface $cartIntegrationNoteRepository,
        private CartIntegrationNoteInterfaceFactory $cartIntegrationNoteFactory,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(Quote $cart, string|null $note): void
    {
        try {
            $cartIntegrationNote = $this->cartIntegrationNoteRepository->getByParentId((int)$cart->getId());
            if (!$cartIntegrationNote->getId()) {
                $cartIntegrationNote->setParentId((int)$cart->getId());
            }
            $cartIntegrationNote->setNote($note);
            $this->cartIntegrationNoteRepository->save($cartIntegrationNote);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('Error while save order notes data:' . $e->getMessage());
        }
    }
}
