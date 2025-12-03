<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Api\Data;

/**
 * @codeCoverageIgnore
 */

interface UserworkspaceInterface
{

    const CUSTOMER_ID = 'customer_id';
    const UPDATED_AT = 'updated_at';
    const APPLICATION_TYPE = 'application_type';
    const CREATED_AT = 'created_at';
    const WORKSPACE_DATA = 'workspace_data';
    const OLD_UPLOAD_DATE = 'old_upload_date';
    const USERWORKSPACE_ID = 'userworkspace_id';

    /**
     * Get userworkspace_id
     * @return string|null
     */
    public function getUserworkspaceId();

    /**
     * Set userworkspace_id
     * @param string $userworkspaceId
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setUserworkspaceId($userworkspaceId);

    /**
     * Get customer_id
     * @return string|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param string $customerId
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get workspace_data
     * @return string|null
     */
    public function getWorkspaceData();

    /**
     * Set workspace_data
     * @param string $workspaceData
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setWorkspaceData($workspaceData);

    /**
     * Get application_type
     * @return string|null
     */
    public function getApplicationType();

    /**
     * Set application_type
     * @param string $applicationType
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setApplicationType($applicationType);

    /**
     * Get old_upload_date
     * @return string|null
     */
    public function getOldUploadDate();

    /**
     * Set old_upload_date
     * @param string $oldUploadDate
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setOldUploadDate($oldUploadDate);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Fedex\FXOCMConfigurator\Userworkspace\Api\Data\UserworkspaceInterface
     */
    public function setUpdatedAt($updatedAt);
}

