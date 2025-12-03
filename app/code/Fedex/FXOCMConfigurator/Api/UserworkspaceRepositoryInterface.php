<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Api;

/**
 * @codeCoverageIgnore
 */

use Magento\Framework\Api\SearchCriteriaInterface;

interface UserworkspaceRepositoryInterface
{

    /**
     * Save userworkspace
     * @param \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface $userworkspace
     * @return \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface $userworkspace
    );

    /**
     * Retrieve userworkspace
     * @param string $userworkspaceId
     * @return \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($userworkspaceId);

    /**
     * Delete userworkspace
     * @param \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface $userworkspace
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface $userworkspace
    );

    /**
     * Delete userworkspace by ID
     * @param string $userworkspaceId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($userworkspaceId);
}

