<?php

namespace Fedex\EnvironmentManager\Block\System\Config;

use Fedex\EnvironmentManager\Api\RetiredToggleManager;
use Magento\Framework\View\Element\Template;

class FlushRetiredToggles extends Template
{
    /**
     * Block template File
     *
     * @var string
     */
    protected $_template = 'Fedex_EnvironmentManager::flushRetiredToggles.phtml';

    public function __construct(
        Template\Context     $context,
        protected RetiredToggleManager $retiredToggleManager,
        array                $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getFlushRetiredTogglesUrl()
    {
        return $this->_urlBuilder->getUrl('enviromentmanager/flushretiredtoggles');
    }

    public function getTogglesToBeFlushed()
    {
        return $this->retiredToggleManager->getTogglesToBeFlushed();
    }
}
