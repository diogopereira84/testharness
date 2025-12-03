<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CustomerGroup\Ui\Component\Form\Field;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Form\Field;

/**
 * Class CustomerGroup.
 */
class CustomerGroup extends Field
{
    /**
     * Field config key.
     */
    const FIELD_CONFIG_KEY = 'config';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param Registry $coreRegistry
     * @param array|UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private GroupRepositoryInterface $groupRepository,
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
     * @return array
     */
    protected function getConfigDefaultData()
    {
        $groupId = $this->coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        if($groupId) {
            $group = $this->groupRepository->getById($groupId);
            return [
                'value' => $group->getCode(),
                'formElement' => 'input'
            ];
        } else {
            return [
                'value' => '',
                'formElement' => 'input'
            ];
         }
    }
}
