<?php

namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class GroupType extends Column
{
    const XML_PATH_USER_GROUP_ORDER_APPROVERS = 'commercial_user_group_order_approvers';

    /**
     * GroupType class constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ToggleConfig $toggleConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        public ToggleConfig $toggleConfig,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    public function prepare()
    {
        $isColumnEnabled = $this->toggleConfig
                                ->getToggleConfigValue(self::XML_PATH_USER_GROUP_ORDER_APPROVERS);

        if (!$isColumnEnabled) {
            $this->_data['config']['componentDisabled'] = true;
        }
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName] = match($item[$fieldName] ?? null) {
                    'order_approval' => __('Order Approval')->render(),
                    'folder_permissions' => __('Folder Permissions')->render(),
                    default => $item[$fieldName] ?? '',
                };
            }
        }

        return $dataSource;
    }
}
