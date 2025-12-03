<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Ui\Component\Form\Field;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;

/**
 * Class GroupId.
 */
class GroupId extends \Magento\Ui\Component\Form\Field
{
    /**
     * Field config key.
     */
    const FIELD_CONFIG_KEY = 'config';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Registry $coreRegistry
     * @param array|UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        protected Registry $coreRegistry,
        array $components,
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    
    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $this->setData(
            self::FIELD_CONFIG_KEY,
            array_replace_recursive(
                (array) $this->getData(self::FIELD_CONFIG_KEY),
                (array) $this->getConfigDefaultData()
            )
        );

        parent::prepare();
    }

    /**
     * Get field config default data.
     *
     * @return array|null
     */
    public function getConfigDefaultData()
    {
        $groupId = $this->coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);

        if($groupId) {
            return [
                'value' => $groupId,
                'formElement' => 'hidden'
            ];
        } else {
            return [
                'value' => -1,
                'formElement' => 'hidden'
            ];
        }
    }
}
