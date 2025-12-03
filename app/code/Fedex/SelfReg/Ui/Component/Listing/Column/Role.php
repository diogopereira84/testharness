<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SelfReg\ViewModel\CompanyUser;

class Role extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ToggleConfig $toggleConfig
     * @param SelfReg $selfReg
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        public ToggleConfig $toggleConfig,
        public SelfReg $selfReg,
        public CompanyUser $companyUser,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare component configuration
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        $isFolderLevelPermissionToggleEnabled = $this->companyUser->toggleUserGroupAndFolderLevelPermissions();
        if ($isFolderLevelPermissionToggleEnabled) {
            $this->_data['config']['componentDisabled'] = true;
        } else {
            $this->_data['config']['componentDisabled'] = false;
        }
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!$this->companyUser->toggleCustomerRolesAndPermissions()) {
            return $dataSource;
        }
        if (isset ($dataSource['data']['items'])) {
            $fieldName = 'role_name';
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset ($item[$fieldName]) && $item[$fieldName] == "Company Administrator") {
                    $item[$fieldName] = "Admin";
                    break;
                }
            }
        }
        return $dataSource;
    }
}
