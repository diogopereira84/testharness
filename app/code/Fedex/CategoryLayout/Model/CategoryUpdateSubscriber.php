<?php

namespace Fedex\CategoryLayout\Model;

use Fedex\SharedCatalogCustomization\Api\MessageInterface;
use Fedex\SharedCatalogCustomization\Api\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class CategoryUpdateSubscriber implements SubscriberInterface
{
    /**
     * CategoryUpdateSubscriber constructor.
     * @param LoggerInterface $logger
     * @param CategoryFactory $categoryFactory
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryRepositoryInterface $categoryRepositoryInterface
    ) {
    }

    /**
     * @inheritdoc
     */
    public function processMessage(MessageInterface $message)
    {
        $categoryUpdateData = $message->getMessage();

        $categoryUpdateDataDecoded = json_decode($categoryUpdateData, true);

        $categoryId = $categoryUpdateDataDecoded['categoryId'];
        $browseCatalogCatId = $categoryUpdateDataDecoded['browseCatalogCatId'];

        $category = $this->categoryFactory->create()->load($categoryId);

        if ($category->getData('is_anchor')) {
            try {
                $category->setCustomAttributes([
                    'is_anchor' => 0
                ]);
                $this->categoryRepositoryInterface->save($category);
                $this->logger->info(__METHOD__.':'.__LINE__.  ' Category is_anchor was updated for category: ' .
                     $categoryId .' under Browse catalog Category: ' . $browseCatalogCatId);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__.':'.__LINE__. ' Error with category update for category ' .
                $categoryId .' under Browse catalog Category: ' . $browseCatalogCatId . ' is: ' . var_export($e->getMessage(), true));
            }
        }
    }
}

