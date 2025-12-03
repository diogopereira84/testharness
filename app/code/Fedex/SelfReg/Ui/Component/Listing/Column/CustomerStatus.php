<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\Commercial\Helper\CommercialHelper;
use Psr\Log\LoggerInterface;

class CustomerStatus extends \Magento\Ui\Component\Listing\Columns\Column
{

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param commercialHelper $commercialHelper
     * @param array $components
     * @param array $data
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected CommercialHelper $commercialHelper,
        protected LoggerInterface $logger,
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
            $fieldName = 'customer_status';
            foreach ($dataSource['data']['items'] as &$item) {
                if ($this->commercialHelper->isSelfRegAdminUpdates()) {
                    if (isset($item[$fieldName])) {
                        $item[$fieldName] = $this->setStatusLabel($item[$fieldName]);
                    } else {
                        $item[$fieldName] = $item['status']->getText();
                    }
                } else {
                    $item[$fieldName] = $item['status']->getText();
                }
            }
        }
        return $dataSource;
    }

    /**
     * Set status label.
     *
     * @param array $item
     * @return labels
     */
    protected function setStatusLabel($key)
    {
        $labels = [
            0 => __('Inactive'),
            1 => __('Active'),
            2 => __('Pending Approval'),
            3 => __('Email Verification Pending'),
        ];

        return $labels[$key] ?? "";
    }
}
