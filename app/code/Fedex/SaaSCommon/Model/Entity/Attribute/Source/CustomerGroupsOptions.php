<?php

declare(strict_types=1);

namespace Fedex\SaaSCommon\Model\Entity\Attribute\Source;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;

class CustomerGroupsOptions extends AbstractSource
{
    /**
     * @param GroupRepositoryInterface $groupRepositoryRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private GroupRepositoryInterface    $groupRepositoryRepository,
        private SearchCriteriaBuilder       $searchCriteriaBuilder
    ) {
    }

    /**
     * Retrieve all options array
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllOptions(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $customerGroupList = $this->groupRepositoryRepository->getList($searchCriteria)->getItems();

        $options = [
            [
                'label' => 'All Groups',
                'value' => '-1',
            ]
        ]; // '-1' represents all groups

        foreach ($customerGroupList as $customerGroup) {
            $options[] = [
                'label' => $customerGroup->getCode(),
                'value' => $customerGroup->getId(),
            ];
        }

        return $options;
    }

    /**
     * Retrieve all options values array
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllOptionsValues(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $customerGroupList = $this->groupRepositoryRepository->getList($searchCriteria)->getItems();

        $options = ['-1']; // '-1' represents all groups

        foreach ($customerGroupList as $customerGroup) {
            $options[] = $customerGroup->getId();
        }

        return $options;
    }
}
