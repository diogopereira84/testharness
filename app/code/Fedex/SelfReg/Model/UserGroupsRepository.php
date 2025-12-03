<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Exception;
use Fedex\SelfReg\Api\Data\UserGroupsInterface;
use Fedex\SelfReg\Api\Data\UserGroupsInterfaceFactory;
use Fedex\SelfReg\Api\Data\UserGroupsSearchResultsInterfaceFactory;
use Fedex\SelfReg\Api\UserGroupsRepositoryInterface;
use Fedex\SelfReg\Model\ResourceModel\UserGroups as ResourceUserGroups;
use Fedex\SelfReg\Model\ResourceModel\UserGroups\CollectionFactory as UserGroupsCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class UserGroupsRepository implements UserGroupsRepositoryInterface
{
    /**
     * UserGroupsRepository class constructor
     *
     * @param ResourceUserGroups $resource
     * @param UserGroupsInterfaceFactory $userGroupsFactory
     * @param UserGroupsCollectionFactory $userGroupsCollectionFactory
     * @param UserGroupsSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        protected ResourceUserGroups $resource,
        protected UserGroupsInterfaceFactory $userGroupsFactory,
        protected UserGroupsCollectionFactory $userGroupsCollectionFactory,
        protected UserGroupsSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function save(UserGroupsInterface $userGroups)
    {
        try {
            $this->resource->save($userGroups);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the userGroups: %1',
                $exception->getMessage()
            ));
        }
        return $userGroups;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        $userGroups = $this->userGroupsFactory->create();
        $this->resource->load($userGroups, $id);
        if (!$userGroups->getId()) {
            throw new NoSuchEntityException(__('user_groups with id "%1" does not exist.', $id));
        }
        return $userGroups;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->userGroupsCollectionFactory->create();

        $this->collectionProcessor
            ->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(UserGroupsInterface $userGroups)
    {
        try {
            $userGroupsModel = $this->userGroupsFactory->create();
            $this->resource->load($userGroupsModel, $userGroups->getId());
            $this->resource->delete($userGroupsModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the user_groups: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        return $this->delete($this->get($id));
    }
}

