<?php

namespace Fedex\Shipto\Block;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\View\Element\Template\Context;

class ManageLocalStorage extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig
     * @param \Magento\Cms\Model\Page $cmsPage
     * 
     */
    public function __construct(
        Context $context,
        public ToggleConfig $toggleConfig,
        public \Magento\Cms\Model\Page $cmsPage
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     * 
     */
    public function removeLocalStorage()
    {
        if ($this->cmsPage->getIdentifier() == 'success') {
            return true;
        }
        return false;
    }
}
