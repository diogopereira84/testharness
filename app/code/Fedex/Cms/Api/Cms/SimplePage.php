<?php

declare(strict_types=1);

namespace Fedex\Cms\Api\Cms;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Cms\Model\PageRepository;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

class SimplePage
{
    /** @var array */
    protected $elements;

    /**
     * UpdateConfig constructor.
     * @param GetPageByIdentifierInterface $getPageByIdentifier
     * @param LoggerInterface $logger
     * @param PageInterfaceFactory $pageInterfaceFactory
     * @param PageRepository $pageRepository
     */
    public function __construct(
        protected GetPageByIdentifierInterface $getPageByIdentifier,
        protected LoggerInterface $logger,
        protected PageInterfaceFactory $pageInterfaceFactory,
        protected PageRepository $pageRepository
    ) {
        $this->elements = [
            'content',
            'content_heading',
            'custom_layout_update_xml',
            'custom_root_template',
            'custom_theme',
            'custom_theme_from',
            'custom_theme_to',
            'is_active',
            'layout_update_selected',
            'layout_update_xml',
            'meta_description',
            'meta_keywords',
            'meta_title',
            'page_layout',
            'sort_order',
            'stores',
            'title',
        ];
    }

    /**
     * Delete a page from a given identifier and optional store id.
     *
     * @param string $identifier
     * @param int $storeId
     */
    public function delete(string $identifier, int $storeId = Store::DEFAULT_STORE_ID): void
    {
        try {
            $page = $this->getPageByIdentifier->execute($identifier, $storeId);
            $this->pageRepository->delete($page);
        } catch (NoSuchEntityException | CouldNotDeleteException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * If the CMS page identifier is found, attempt to update the record.
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
            $page = $this->getPageByIdentifier->execute($identifier, $storeId);
        } catch (NoSuchEntityException $e) {
            // Rather than throwing an exception, create a new page instance
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
            ' CMS block identifier not found, attempting to create new record.');

            /** @var PageInterface|AbstractModel $page */
            $page = $this->pageInterfaceFactory->create();
            $page->setIdentifier($identifier);

            // Set initial store data to "all stores"
            $page->setData('store_id', $storeId);
            $page->setData('stores', [$storeId]);

            // Set a default page layout
            $page->setData('page_layout', '1column');
        }

        foreach ($this->elements as $element) {
            if (isset($data[$element])) {
                $page->setData($element, $data[$element]);
            }
        }

        try {
            $this->pageRepository->save($page);
        } catch (CouldNotSaveException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }
}
