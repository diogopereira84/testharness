<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\EnvironmentManager\Helper;

use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Module\Manager;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ModuleStatus extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Data Constructor
     *
     * @param Context $context
     * @param Manager $moduleManager
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected Context $context,
        protected Manager $moduleManager,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Is module enable
     *
     * @param string $moduleName
     * @return bool true|false
     */
    public function isModuleEnable($moduleName)
    {
        return $this->moduleManager->isEnabled($moduleName);
    }
}
