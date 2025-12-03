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

class CustomerEmail extends \Magento\Ui\Component\Listing\Columns\Column
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
            $fieldName = 'email';
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = $this->setCustomerEmail($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Set status label.
     *
     * @param int $key
     * @return string
     */
    protected function setCustomerEmail($item)
    {
		$email = $item['email'];
		if ($this->selfReg->isSelfRegCustomer() || $this->selfReg->isSelfRegCustomerAdmin()){
			$email = isset($item['secondary_email']) ? $item['secondary_email'] : null;
		}
		return $email;
    }
}
