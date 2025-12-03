<?php

/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Plugin\UiComponent;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider as BaseDataProvider;
use Fedex\SelfReg\ViewModel\CompanyUser;

class DataProviderPlugin
{
    /**
     * Find Group class constructor
     * @param RequestInterface $request
     * @param CompanyUser $companyUser
     *
     */
    public function __construct(
        private RequestInterface $request,
        private CompanyUser $companyUser
    ) {
    }

    /**
     * Get Search results
     *
     * @param BaseDataProvider $subject
     * @param $result
     *
     * @return $result
     */
    public function afterGetSearchResult(BaseDataProvider $subject, $result)
    {
        $isFolderLevelPermissionToggleEnabled = $this->companyUser->toggleUserGroupAndFolderLevelPermissions();

        if ($isFolderLevelPermissionToggleEnabled &&
            ($subject->getName() === 'selfreg_users_manageusergroups_listing_data_source')) {
            $postData = $this->request->getParams();

            if (isset($postData['sorting']['field']) && !empty($postData['sorting']['field'])) {
                $field = $postData['sorting']['field'];
                if ($field == 'group_name') {
                    $field = 'group_name';
                }
                $direction = strtoupper($postData['sorting']['direction']);
                $result->getSelect()->order("$field $direction");
            } else {
                $result->getSelect()->order("id ASC");
            }

            if (isset($postData['search']) && !empty($postData['search'])) {
                $searchdata = $postData['search'];
                $result->getSelect()
                ->where("main_table.group_name LIKE ?", "%" . $searchdata . "%");
            }
        }

        return $result;
    }
}
