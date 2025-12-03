<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Psr\Log\LoggerInterface;

class AddedDate extends \Magento\Ui\Component\Listing\Columns\Column
{
	/**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ToggleConfig $toggleConfig
     * @param SelfReg $selfReg
     * @param array $components
     * @param array $data
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected ToggleConfig $toggleConfig,
        protected SelfReg $selfReg,
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
            $fieldName = 'created_at';
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $this->setDateAdded($item);
                }
            }
        }
        return $dataSource;
    }

    /**
     * Set status label.
     *
     * @param array $item
     * @return date
     */
    protected function setDateAdded($item)
    {
        $date = $item['created_at'];
		return date("M d, Y", strtotime($date));
    }
}
