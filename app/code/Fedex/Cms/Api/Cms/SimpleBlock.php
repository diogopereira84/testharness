<?php

declare(strict_types=1);

namespace Fedex\Cms\Api\Cms;

use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Api\GetBlockByIdentifierInterface;
use Magento\Cms\Model\BlockRepository;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class SimpleBlock
{
    /**
     * UpdateConfig constructor.
     * @param BlockInterfaceFactory $blockInterfaceFactory
     * @param BlockRepository $blockRepository
     * @param GetBlockByIdentifierInterface $getBlockByIdentifier
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected BlockInterfaceFactory $blockInterfaceFactory,
        protected BlockRepository $blockRepository,
        protected GetBlockByIdentifierInterface $getBlockByIdentifier,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Delete a block from a given identifier and optional store id.
     *
     * @param string $identifier
     * @param int $storeId
     */
    public function delete(string $identifier, int $storeId = Store::DEFAULT_STORE_ID): void
    {
        try {
            $this->getBlockByIdentifier->execute($identifier, $storeId);
        } catch (NoSuchEntityException | CouldNotDeleteException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * If the CMS block identifier is found, attempt to update the record.
     *
     * If it is not found, attempt to create a new record.
     *
     * @param array $data
     */
    public function save(array $data): void
    {
        $identifier = $data['identifier'];
        $storeId = $data['store_id'] ?? Store::DEFAULT_STORE_ID;

        try {
            $block = $this->getBlockByIdentifier->execute($identifier, $storeId);
        } catch (NoSuchEntityException $e) {
            // Rather than throwing an exception, create a new block instance
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            ' CMS block identifier not found, attempting to create new record.');

            /** @var BlockInterface|AbstractModel $block */
            $block = $this->blockInterfaceFactory->create();
            $block->setIdentifier($identifier);

            // Set initial store data to "all stores"
            $block->setData('store_id', $storeId);
            $block->setData('stores', [$storeId]);
        }

        $elements = [
            'content',
            'is_active',
            'stores',
            'title',
        ];

        foreach ($elements as $element) {
            if (isset($data[$element])) {
                $block->setData($element, $data[$element]);
            }
        }

        try {
            $this->blockRepository->save($block);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
