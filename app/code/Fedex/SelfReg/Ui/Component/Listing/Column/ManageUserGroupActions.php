<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\GroupFactory;

/**
 * Class ManageUserGroupActions to handle edit and delete actions
 */
class ManageUserGroupActions extends Column
{
    /**
     * ManageUserGroupActions constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param GroupFactory $groupFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private GroupFactory $groupFactory,
        private UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $provider = 'manage_user_groups_listing.selfreg_users_manageusergroups_listing_data_source';
                if (isset($item['id'])) {
                    $item['actions'] = [
                        'edit' => [
                            'href' => '#',
                            'label' => __('Edit'),
                            'hidden' => false,
                            'type' => 'edit-group',
                            'id' => $item['id'],
                            'site_url' => $item['site_url'],
                        ],
                        'delete' => [
                            'href' => '#',
                            'label' => __('Delete'),
                            'hidden' => false,
                            'type' => 'delete-group',
                            'options' => [
                                'id' => $item['id'],
                                'deleteUrl' => $this->urlBuilder->getUrl('company/user/delete'),
                                'gridProvider' => $provider,
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
