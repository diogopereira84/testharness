<?php

namespace Fedex\EnvironmentManager\Block\System\Config;

use Fedex\EnvironmentManager\Api\RetiredToggleManager;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Structure;
use \Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;

class Edit extends \Magento\Config\Block\System\Config\Edit
{
    public const TOGGLE_SECTION_NAME = 'environment_toggle_configuration';

    public function __construct(
        Context $context,
        Structure $configStructure,
        protected RetiredToggleManager $retiredToggleManager,
        array $data = [],
        Json $jsonSerializer = null
    ) {
        parent::__construct($context, $configStructure, $data, $jsonSerializer);
    }

    /**
     * Prepare layout object
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        /** @var $section \Magento\Config\Model\Config\Structure\Element\Section */
        $section = $this->_configStructure->getElement($this->getRequest()->getParam('section'));

        if ($section->getId() == self::TOGGLE_SECTION_NAME) {
            $isDisabled = $this->retiredToggleManager->getTogglesToBeFlushed() === '';
            /**
             * @var Template $toolbar;
             */
            $toolbar = $this->getToolbar();
            $toolbar->addChild(
                'flush_retired_toggles',
                \Magento\Backend\Block\Widget\Button::class,
                [
                    'id' => 'flush_retired_toggles_button',
                    'label' => __('Flush Retired Toggles'),
                    'class' => 'flush-retired-toggles',
                    'disabled' => $isDisabled
                ]
            );
        }
        return parent::_prepareLayout();
    }
}
