<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Commercial\Helper\CommercialHelper;

class CheckBox extends \Magento\Ui\Component\Listing\Columns\Column
{
	/**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ToggleConfig $toggleConfig
     * @param CommercialHelper $commercialHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        public ToggleConfig $toggleConfig,
        public CommercialHelper $commercialHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    public function prepare()
    {
        $visiblity  = false;
        $isRolesAndPermissionEnabled = $this->commercialHelper->isRolePermissionToggleEnable();
        if ($isRolesAndPermissionEnabled) {
            $visiblity = true;
        }
        $this->_data['config']['visible'] = $visiblity;
        parent::prepare();
    }
}
