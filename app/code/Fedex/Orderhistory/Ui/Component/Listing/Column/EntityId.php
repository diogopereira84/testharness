<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class EntityId
 */
class EntityId extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Constructor
     * B-1096386 | Display Quote ID instead on quote name on My Quotes screen
     * 
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Fedex\Orderhistory\Helper\Data $helperData
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected \Fedex\Orderhistory\Helper\Data $helperData,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    /**
     * Prepare DataSource
     * B-1130766
     * @return array
     */
    public function prepareDataSource(array $dataSource){
		if (isset($dataSource['data']['items'])) {
			foreach ($dataSource['data']['items'] as & $item) {
				if($this->getData('name') == 'entity_id') {
				   $item[$this->getData('name')] = $item[$this->getData('name')] . '-SEPO';
				}
			}
		}

		return $dataSource;
	}
	
    /**
     * Prepare component configuration
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        
        $isModuleEnabled = $this->helperData->isModuleEnabled();
        if (!$isModuleEnabled) {
            $this->_data['config']['componentDisabled'] = true;
        }
    }
}
