<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Ui\Component\Listing\Column;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Change admin actions for change admin grid
 */
class ChangeAdminActions extends Column
{
    /**
     * Constructor for ChangeAdminActions
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param RequestInterface $request
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected RequestInterface $request,
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

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $adminId = $this->request->getParam('adminId') ?? '';
        $oldAdminId = $this->request->getParam('oldAdminId') ?? '';
        $curSavedAdminId = $this->request->getParam('savedAdminId') ?? '';

        if (isset($dataSource['data']['items'])) {
            $provider = 'change_admin_grid.change_admin_grid_data_source';

            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id']) && isset($item['customer_role']) && isset($item['status'])) {
                    if ($item['status'] == '1') {
                        if ($oldAdminId && $item['entity_id'] == $oldAdminId) {
                            $item['customer_role'] = '1';
                        } elseif ($adminId && $item['entity_id'] == $adminId) {
                            $item['customer_role'] = '0';
                        } elseif ($item['customer_role'] == '0') {
                            if ($curSavedAdminId && $curSavedAdminId != $item['entity_id']) {
                                $item['customer_role'] = '1';
                            } elseif (($adminId || $oldAdminId) && $adminId != $item['entity_id']) {
                                $item['customer_role'] = '1';
                            }
                        } elseif ($curSavedAdminId && $curSavedAdminId == $item['entity_id']) {
                            if ($adminId && $adminId != $curSavedAdminId) {
                                $item['customer_role'] = '1';
                            } else {
                                $item['customer_role'] = '0';
                            }
                        }
                        if ($item['customer_role'] == '0') {
                            $item[$this->getData('name')] = [
                                'removeAdmin' => [
                                    'label' => __('Remove Admin'),
                                    'type' => 'remove-admin',
                                    'options' => [
                                        'gridProvider' => $provider
                                    ]
                                ]
                            ];
                        } else {
                            $item[$this->getData('name')] = [
                                'makeAdmin' => [
                                    'label' => __('Make Admin'),
                                    'type' => 'make-admin',
                                    'options' => [
                                        'gridProvider' => $provider
                                    ]
                                ]
                            ];
                        }
                    } else {
                        // If the user is not active, remove actions
                        $item[$this->getData('name')] = [
                            'makeAdminDisabled' => [
                                'label' => __('Make Admin'),
                                'type' => 'make-admin-disabled',
                                'disabled' => true,
                            ]
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
