<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\PersonalAddressBook\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Fedex\PersonalAddressBook\Helper\Parties;
use Magento\Theme\Block\Html\Pager;
use Fedex\Company\Model\Config\Source\States;
use Magento\Customer\Model\Session;

/**
 * View Block class
 */
class View extends Template
{
    /**
     * @var $totalRecords
     */
    protected $totalRecords;

    /**
     * Initializing Constructor
     *
     * @param Context $context
     * @param Parties $partiesHelper
     * @param Session $customerSession
     * @param States $state
     */
    public function __construct(
        Context $context,
        protected Parties $partiesHelper,
        protected Session $customerSession,
        public States $state
    ) {
        parent::__construct($context);
        $this->totalRecords = 0;
    }

    /**
     * Prepare layout for template.
     *
     * @return PersonalAddressBook
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        return $this;
    }

    /**
     * Get Address Book Data
     * @return array
     */
    public function addressBookData()
    {
        $data = $this->partiesHelper->callGetPartiesList();

        if (!empty($data['output']['partyList'])) {
            $partiesList = $data['output']['partyList'];
            array_multisort(array_map('strtolower',array_column($partiesList, 'lastName')), SORT_ASC, $partiesList);
            $this->customerSession->setPartiesList(json_encode($partiesList));
            $this->totalRecords = count($partiesList);
            $pageSize = $this->customerSession->getAddressBookPageSize();
            if (!empty($pageSize)) {
                return $this->partiesHelper->paginatedData($partiesList, $pageSize);
            }
            return $this->partiesHelper->paginatedData($partiesList);
        }

        return [];
    }

    /**
     * Get State Option array
     * @return array
     */
    public  function getStateOption()
    {
        $states = $this->state->toOptionArray();
        $stateCodesArray = array_filter(array_map(function($state) {
                  return $state['value'];
                },$states));
        return $stateCodesArray;
    }

    /**
     * Get Total Records of Addressbook
     * @return int
     */
    public function totalRecords() 
    {
        return $this->totalRecords;
    }
}
