<?php

declare(strict_types=1);

namespace Fedex\CatalogMigration\Model\Entity\Attribute\Source;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SharedCatalog\Api\SharedCatalogRepositoryInterface;

class SharedCatalogOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @param SharedCatalogRepositoryInterface $sharedCatalogRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private SharedCatalogRepositoryInterface $sharedCatalogRepository,
        private SearchCriteriaBuilder            $searchCriteriaBuilder
    ) {
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $catalogList = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();

        $options = [];

        /** @var \Magento\SharedCatalog\Api\Data\SharedCatalogInterface $item */
        foreach ($catalogList as $item) {
            $options[] = [
                'label' => $item->getName(),
                'value' => $item->getId(),
            ];
        }

        return $options;
    }
}