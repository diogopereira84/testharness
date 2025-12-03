<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipment\Block\Adminhtml\Shipment\Edit\Tab;

/**
 * Shipment edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    public const ITEM_INFORMATION = 'Item Information';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        protected \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @codeCoverageIgnore
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /* @var $model \Fedex\Shipment\Model\BlogPosts */
        $model = $this->_coreRegistry->registry('shipment');
        $isElementDisabled = false;
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('page_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __(self::ITEM_INFORMATION)]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'label',
            'text',
            [
                'name' => 'label',
                'label' => __('Status Label'),
                'title' => __('Status Label'),
                'required' => true,
                'nullable' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'value',
            'text',
            [
                'name' => 'value',
                'label' => __('Status Value'),
                'title' => __('Status Value'),
                'required' => true,
                'nullable' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        $fieldset->addField(
            'key',
            'text',
            [
                'name' => 'key',
                'label' => __('Status Key'),
                'title' => __('Status Key'),
                'required' => true,
                'nullable' => false,
                'disabled' => $isElementDisabled,
            ]
        );
        if (!$model->getId()) {
            $model->setData('is_active', $isElementDisabled ? '0' : '1');
        }
        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __(self::ITEM_INFORMATION);
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __(self::ITEM_INFORMATION);
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    public function getTargetOptionArray()
    {
        return [
            '_self' => "Self",
            '_blank' => "New Page",
        ];
    }
}
