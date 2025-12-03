<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Model;

use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface;
use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceSearchResultsInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\UserworkspaceRepositoryInterface;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace as ResourceUserworkspace;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\CollectionFactory as UserworkspaceCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class UserworkspaceRepository implements UserworkspaceRepositoryInterface
{

    /**
     * @param ResourceUserworkspace $resource
     * @param UserworkspaceInterfaceFactory $userworkspaceFactory
     * @param UserworkspaceCollectionFactory $userworkspaceCollectionFactory
     * @param UserworkspaceSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        protected ResourceUserworkspace $resource,
        protected UserworkspaceInterfaceFactory $userworkspaceFactory,
        protected UserworkspaceCollectionFactory $userworkspaceCollectionFactory,
        protected UserworkspaceSearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function save(UserworkspaceInterface $userworkspace)
    {
        try {
            $this->resource->save($userworkspace);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the userworkspace: %1',
                $exception->getMessage()
            ));
        }
        return $userworkspace;
    }

    /**
     * @inheritDoc
     */
    public function get($userworkspaceId)
    {
        $userworkspace = $this->userworkspaceFactory->create();
        $this->resource->load($userworkspace, $userworkspaceId);
        if (!$userworkspace->getId()) {
            throw new NoSuchEntityException(__('userworkspace with id "%1" does not exist.', $userworkspaceId));
        }
        return $userworkspace;
    }

    /**
     * @inheritDoc
     */
    public function delete(UserworkspaceInterface $userworkspace)
    {
        try {
            $userworkspaceModel = $this->userworkspaceFactory->create();
            $this->resource->load($userworkspaceModel, $userworkspace->getUserworkspaceId());
            $this->resource->delete($userworkspaceModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the userworkspace: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($userworkspaceId)
    {
        return $this->delete($this->get($userworkspaceId));
    }
}

