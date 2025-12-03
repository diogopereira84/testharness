<?php

declare(strict_types=1);

namespace Fedex\ProductEngine\Block\Adminhtml\Product\Attribute\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Eav\Block\Adminhtml\Attribute\PropertyLocker;
use Magento\Eav\Helper\Data;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Form\Element\Fieldset;

/**
 * @codeCoverageIgnore
 */
class Advanced extends \Magento\Catalog\Block\Adminhtml\Product\Attribute\Edit\Tab\Advanced
{
    /**
     * Eav data
     *
     * @var Data
     */
    protected $_eavData = null;

    /**
     * @var Yesno
     */
    protected $_yesNo;

    /**
     * @var array
     */
    protected $disableScopeChangeList;

    /**
     * @var PropertyLocker
     */
    private $propertyLocker;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    CONST IS_GLOBAL = 'is_global';
    CONST IS_PRODUCT_LEVEL_DEFAULT = 'is_product_level_default';
    CONST IS_FILTERABLE_IN_GRID = 'is_filterable_in_grid';
    CONST IS_VISIBLE_IN_GRID = 'is_visible_in_grid';
    CONST IS_USED_IN_GRID = 'is_used_in_grid';
    CONST IS_UNIQUE = 'is_unique';
    CONST VALUES = 'values';
    CONST SELECT = 'select';
    CONST VALUE = 'value';
    CONST DEFAULT_VALUE = 'Default Value';
    CONST TITLE = 'title';
    CONST LABEL = 'label';
    CONST ATTRIBUTE_CODE = 'attribute_code';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param Yesno $yesNo
     * @param Data $eavData
     * @param array $disableScopeChangeList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        Yesno $yesNo,
        Data $eavData,
        array $disableScopeChangeList = ['sku'],
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $yesNo, $eavData, $disableScopeChangeList, $data);
        $this->logger = $context->getLogger();
    }

    /**
     * Adding product form elements for editing attribute
     *
     * @return $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD)
     */
    protected function _prepareForm()
    {
        $attributeObject = $this->getAttributeObject();

        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $fieldset = $form->addFieldset(
            'advanced_fieldset',
            ['legend' => __('Advanced Attribute Properties'), 'collapsable' => true]
        );

        $fieldset = $this->_prepareFields($fieldset, $attributeObject);

        if ($attributeObject->getId()) {
            $form->getElement(SELF::ATTRIBUTE_CODE)->setDisabled(1);
            if (!$attributeObject->getIsUserDefined()) {
                $form->getElement(SELF::IS_UNIQUE)->setDisabled(1);
            }
        }

        $scopes = [
            \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE => __('Store View'),
            \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE => __('Website'),
            \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL => __('Global'),
        ];

        if ($attributeObject->getAttributeCode() == 'status' || $attributeObject->getAttributeCode() == 'tax_class_id'
        ) {
            unset($scopes[\Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE]);
        }

        $fieldset->addField(
            SELF::IS_GLOBAL,
            SELF::SELECT,
            [
                'name' => SELF::IS_GLOBAL,
                SELF::LABEL => __('Scope'),
                SELF::TITLE => __('Scope'),
                'note' => __('Declare attribute value saving scope.'),
                SELF::VALUES => $scopes
            ],
            SELF::ATTRIBUTE_CODE
        );

        $this->_eventManager->dispatch('product_attribute_form_build', ['form' => $form]);
        if (in_array($attributeObject->getAttributeCode(), $this->disableScopeChangeList)) {
            $form->getElement(SELF::IS_GLOBAL)->setDisabled(1);
        }
        $this->setForm($form);
        $this->getPropertyLocker()->lock($form);
        return $this;
    }

    /**
     * Create fields for form
     * @param Fieldset $fieldset
     * @param Attribute $attributeObject
     * @return Fieldset
     */
    protected function _prepareFields($fieldset, $attributeObject): Fieldset
    {
        $yesno = $this->_yesNo->toOptionArray();

        $validateClass = sprintf(
            'validate-code validate-length maximum-length-%d',
            \Magento\Eav\Model\Entity\Attribute::ATTRIBUTE_CODE_MAX_LENGTH
        );

        $fieldset->addField(
            SELF::ATTRIBUTE_CODE,
            'text',
            [
                'name' => SELF::ATTRIBUTE_CODE,
                SELF::LABEL => __('Attribute Code'),
                SELF::TITLE => __('Attribute Code'),
                'note' => __(
                    'This is used internally. Make sure you don\'t use spaces or more than %1 symbols.',
                    \Magento\Eav\Model\Entity\Attribute::ATTRIBUTE_CODE_MAX_LENGTH
                ),
                'class' => $validateClass
            ]
        );

        $fieldset->addField(
            'default_value_text',
            'text',
            [
                'name' => 'default_value_text',
                SELF::LABEL => __(SELF::DEFAULT_VALUE),
                SELF::TITLE => __(SELF::DEFAULT_VALUE),
                SELF::VALUE => $attributeObject->getDefaultValue()
            ]
        );

        $fieldset->addField(
            'default_value_yesno',
            SELF::SELECT,
            [
                'name' => 'default_value_yesno',
                SELF::LABEL => __(SELF::DEFAULT_VALUE),
                SELF::TITLE => __(SELF::DEFAULT_VALUE),
                SELF::VALUES => $yesno,
                SELF::VALUE => $attributeObject->getDefaultValue()
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'default_value_date',
            'date',
            [
                'name' => 'default_value_date',
                SELF::LABEL => __(SELF::DEFAULT_VALUE),
                SELF::TITLE => __(SELF::DEFAULT_VALUE),
                SELF::VALUE => $attributeObject->getDefaultValue(),
                'date_format' => $dateFormat,
            ]
        );

        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'default_value_datetime',
            'date',
            [
                'name' => 'default_value_datetime',
                SELF::LABEL => __(SELF::DEFAULT_VALUE),
                SELF::TITLE => __(SELF::DEFAULT_VALUE),
                SELF::VALUE => $this->getLocalizedDateDefaultValue(),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
            ]
        );

        $fieldset->addField(
            'default_value_textarea',
            'textarea',
            [
                'name' => 'default_value_textarea',
                SELF::LABEL => __(SELF::DEFAULT_VALUE),
                SELF::TITLE => __(SELF::DEFAULT_VALUE),
                SELF::VALUE => $attributeObject->getDefaultValue()
            ]
        );

        $fieldset->addField(
            SELF::IS_UNIQUE,
            SELF::SELECT,
            [
                'name' => SELF::IS_UNIQUE,
                SELF::LABEL => __('Unique Value'),
                SELF::TITLE => __('Unique Value (not shared with other products)'),
                'note' => __('Not shared with other products.'),
                SELF::VALUES => $yesno
            ]
        );

        $fieldset->addField(
            'frontend_class',
            SELF::SELECT,
            [
                'name' => 'frontend_class',
                SELF::LABEL => __('Input Validation for Store Owner'),
                SELF::TITLE => __('Input Validation for Store Owner'),
                SELF::VALUES => $this->_eavData->getFrontendClasses($attributeObject->getEntityType()->getEntityTypeCode())
            ]
        );

        $fieldset->addField(
            SELF::IS_USED_IN_GRID,
            SELF::SELECT,
            [
                'name' => SELF::IS_USED_IN_GRID,
                SELF::LABEL => __('Add to Column Options'),
                SELF::TITLE => __('Add to Column Options'),
                SELF::VALUES => $yesno,
                SELF::VALUE => $attributeObject->getData(SELF::IS_USED_IN_GRID) ?: 1,
                'note' => __('Select "Yes" to add this attribute to the list of column options in the product grid.'),
            ]
        );

        $fieldset->addField(
            SELF::IS_VISIBLE_IN_GRID,
            'hidden',
            [
                'name' => SELF::IS_VISIBLE_IN_GRID,
                SELF::VALUE => $attributeObject->getData(SELF::IS_VISIBLE_IN_GRID) ?: 1,
            ]
        );

        $fieldset->addField(
            SELF::IS_FILTERABLE_IN_GRID,
            SELF::SELECT,
            [
                'name' => SELF::IS_FILTERABLE_IN_GRID,
                SELF::LABEL => __('Use in Filter Options'),
                SELF::TITLE => __('Use in Filter Options'),
                SELF::VALUES => $yesno,
                SELF::VALUE => $attributeObject->getData(SELF::IS_FILTERABLE_IN_GRID) ?: 1,
                'note' => __('Select "Yes" to add this attribute to the list of filter options in the product grid.'),
            ]
        );

        $fieldset->addField(
            SELF::IS_PRODUCT_LEVEL_DEFAULT,
            SELF::SELECT,
            [
                'name' => SELF::IS_PRODUCT_LEVEL_DEFAULT,
                SELF::LABEL => __('Show Product Level Default Option'),
                SELF::TITLE => __('Show Product Level Default Option'),
                SELF::VALUES => $yesno,
                SELF::VALUE => $attributeObject->getData(SELF::IS_PRODUCT_LEVEL_DEFAULT) ?: 0,
                'note' => __('Select "Yes" to show an extra selection on the product admin page so that you can select a standard option at the product level.'),
            ]
        );

        return $fieldset;
    }

    /**
     * Retrieve attribute object from registry
     *
     * @return Attribute
     */
    private function getAttributeObject()
    {
        return $this->_coreRegistry->registry('entity_attribute');
    }

    /**
     * Get property locker
     *
     * @return PropertyLocker
     */
    private function getPropertyLocker()
    {
        if (null === $this->propertyLocker) {
            $this->propertyLocker = ObjectManager::getInstance()->get(PropertyLocker::class);
        }
        return $this->propertyLocker;
    }

    /**
     * Get localized date default value
     *
     * @return string
     * @throws LocalizedException
     */
    private function getLocalizedDateDefaultValue(): string
    {
        $attributeObject = $this->getAttributeObject();
        if (empty($attributeObject->getDefaultValue()) || $attributeObject->getFrontendInput() !== 'datetime') {
            return (string)$attributeObject->getDefaultValue();
        }

        try {
            $localizedDate = $this->_localeDate->date($attributeObject->getDefaultValue(), null, false);
            $localizedDate->setTimezone(new \DateTimeZone($this->_localeDate->getConfigTimezone()));
            $localizedDate = $localizedDate->format(DateTime::DATETIME_PHP_FORMAT);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' The default date is invalid.');
            throw new LocalizedException(__('The default date is invalid.'));
        }

        return $localizedDate;
    }
}
