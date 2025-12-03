<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\UploadToQuote\Ui\Component\Listing\Column;

use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class CustomCreatedDate extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private AdminConfigHelper $adminConfigHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $items = &$dataSource['data']['items'];

        $quoteIds = array_column($items, 'entity_id');
        $submitDates = $this->adminConfigHelper->getSubmitDates($quoteIds);

        foreach ($items as &$item) {
            $quoteId = $item['entity_id'] ?? null;
            if ($quoteId && isset($submitDates[$quoteId])) {
                $item['created_at'] = $submitDates[$quoteId];
            }
        }

        return $dataSource;
    }
}
