<?php
declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Block\Adminhtml\Group\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\FormFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Tax\Model\TaxClass\Source\Customer;
use Magento\Tax\Helper\Data;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as GroupFactory;
use Magento\Framework\Registry;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Adminhtml customer groups edit form
 */
class Form extends \Magento\Customer\Block\Adminhtml\Group\Edit\Form
{
    const GROUP_CODE_MAX_LENGTH = 32;
    const GROUP_CODE_MAX_LENGTH_EXTENDED = 200;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Customer $taxCustomer
     * @param Data $taxHelper
     * @param GroupRepositoryInterface $groupRepository
     * @param GroupInterfaceFactory $groupDataFactory
     * @param GroupFactory $groupFactory
     * @param ToggleConfig $toggleConfig
     * @param array $data
     * @param SystemStore|null $systemStore
     * @param GroupExcludedWebsiteRepositoryInterface|null $groupExcludedWebsiteRepository
     */
    public function __construct(
        Context                                                                $context,
        Registry                                                               $registry,
        FormFactory                                                            $formFactory,
        protected Customer                                                     $taxCustomer,
        protected Data                                                         $taxHelper,
        protected GroupRepositoryInterface                                     $groupRepository,
        GroupInterfaceFactory                                                  $groupDataFactory,
        protected GroupFactory                                                 $groupFactory,
        protected ToggleConfig                                                 $toggleConfig,
        array                                                                  $data = [],
        private ?SystemStore                                                   $systemStore = null,
        private ?\Magento\Customer\Api\GroupExcludedWebsiteRepositoryInterface $groupExcludedWebsiteRepository = null
    ) {
        $this->groupDataFactory = $groupDataFactory;
        $this->systemStore = $systemStore ?: ObjectManager::getInstance()->get(SystemStore::class);
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $taxCustomer,
            $taxHelper,
            $groupRepository,
            $this->groupDataFactory,
            $data,
            $systemStore
        );
    }

    /**
     * Prepare form for render
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $form = $this->_formFactory->create();
        $groupId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_GROUP_ID);
        $customerGroupExcludedWebsites = [];
        if ($groupId === null) {
            $customerGroup = $this->groupDataFactory->create();
            $defaultCustomerTaxClass = $this->taxHelper->getDefaultCustomerTaxClass();
        } else {
            $customerGroup = $this->groupRepository->getById($groupId);
            $defaultCustomerTaxClass = $customerGroup->getTaxClassId();
        }

        $fieldset = $form->addFieldset('base_fieldset', []);

        $validateClass = sprintf(
            'required-entry validate-length maximum-length-%d',
            static::GROUP_CODE_MAX_LENGTH_EXTENDED
        );
        $name = $fieldset->addField(
            'customer_group_code',
            'text',
            [
                'name' => 'code',
                'label' => __('Group Name'),
                'title' => __('Group Name'),
                'note' => __(
                    'Maximum length must be less then 200 characters.',
                    static::GROUP_CODE_MAX_LENGTH_EXTENDED
                ),
                'class' => $validateClass,
                'required' => true,
            ]
        );
        if ($customerGroup->getId() == 0 && $customerGroup->getCode()) {
            $name->setDisabled(true);
        }
        if ($this->toggleConfig->getToggleConfigValue(static::CATALOG_FOLDER_PERMISSION)) {
            $fieldset->addField(
                'ajax_url',
                'hidden',
                [
                    'name'  => 'ajax_url',
                    'value' => ''.$this->getUrl('customergroup/options/assigngroup'),
                ]
            );
            $fieldset->addField(
                'parent_group_id',
                'select',
                [
                    'label' => __('Parent Group'),
                    'title' => __('Parent Group'),
                    'required' => false,
                    'options' => $this->getParentGroupOptions($groupId),
                    'name' => 'parent_group_code',
                ]
            );
        }
        $fieldset->addField(
            'tax_class_id',
            'select',
            [
                'name' => 'tax_class',
                'label' => __('Tax Class'),
                'title' => __('Tax Class'),
                'class' => 'required-entry',
                'required' => true,
                'values' => $this->taxCustomer->toOptionArray(),
            ]
        );

        $fieldset->addField(
            'customer_group_excluded_website_ids',
            'multiselect',
            [
                'name' => 'customer_group_excluded_websites',
                'label' => __('Excluded Website(s)'),
                'title' => __('Excluded Website(s)'),
                'required' => false,
                'can_be_empty' => true,
                'values' => $this->systemStore->getWebsiteValuesForForm(),
                'note' => __('Select websites you want to exclude from this customer group.'),
            ]
        );

        if ($customerGroup->getId() !== null) {
            // If edit add id
            $form->addField('id', 'hidden', ['name' => 'id', 'value' => $customerGroup->getId()]);
        }

        if ($this->_backendSession->getCustomerGroupData()) {
            $form->addValues($this->_backendSession->getCustomerGroupData());
            $this->_backendSession->setCustomerGroupData(null);
        } else {
            $form->addValues(
                [
                    'id' => $customerGroup->getId(),
                    'customer_group_code' => $customerGroup->getCode(),
                    'tax_class_id' => $defaultCustomerTaxClass,
                    'customer_group_excluded_website_ids' => $customerGroupExcludedWebsites,
                ]
            );
        }

        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setAction($this->getUrl('customer/*/save'));
        $form->setMethod('post');
        $this->setForm($form);
    }
    /**
     * Get parent Group options
     *
     * @param int $id
     * @return array
     */
    protected function getParentGroupOptions($id)
    {

        if ($id === null) {
            $result = ['' => 'Select a group...'];
        } else {
            $customerGroups = $this->groupFactory->create();
            $customerGroups->addFieldToFilter('customer_group_id', $id);
            $data = $customerGroups->getData();
            $result = [];
            foreach ($data as $index => $value) {
                $result = [ $value["parent_group_id"].'' => ''.$this->getParentGroupLabel($value["parent_group_id"])];
            }
        }
        return $result;
    }

    /**
     * Get parent Group Lable
     *
     * @param int $id
     * @return string
     */
    protected function getParentGroupLabel($id)
    {

        $customerGroups = $this->groupFactory->create();
        $lable = '';
        $customerGroups->addFieldToFilter('customer_group_id', $id);
        $data = $customerGroups->getData();
        foreach ($data as $index => $value) {
            $lable = $value['customer_group_code'];
        }
        return $lable;
    }
}
