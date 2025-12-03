<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Checkout\Model\ConfigProviderInterface;


/**
 * ConfigProvider Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const USER_GROUP_FOLDER_LEVEL_PERMISSION = 'sgc_user_group_and_folder_level_permissions';

    /**
     * ConfigProvider Constructor
     *
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Configuration for Manage Users page
     *
     * @return array
     */
    public function getConfig()
    {
        $sgcUserGroupAndFolderLevelPermission = (bool) $this->toggleConfig->getToggleConfigValue(
            self::USER_GROUP_FOLDER_LEVEL_PERMISSION
        );

        return [
            'sgc_user_group_and_folder_level_permissions' => $sgcUserGroupAndFolderLevelPermission,
        ];
    }
}