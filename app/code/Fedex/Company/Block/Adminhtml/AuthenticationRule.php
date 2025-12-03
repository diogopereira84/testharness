<?php

namespace Fedex\Company\Block\Adminhtml;

use Magento\Company\Api\CompanyRepositoryInterface;

class AuthenticationRule extends \Magento\Backend\Block\Template
{

    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'rule.phtml';

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        /**
         * @var $ruleFactory \Fedex\Company\Model\AuthDynamicRowsFactory
         */
        protected \Fedex\Company\Model\AuthDynamicRowsFactory $ruleFactory,
        /**
         * @var $companyRepository Magento\Company\Api\CompanyRepositoryInterface
         */
        protected CompanyRepositoryInterface $companyRepository
    ) {

        parent::__construct($context);
    }

    /**
     * get Rules
     *
     * @param filter $filter
     *
     * @return collection $collection
     */
    public function getRules($filter)
    {
        $request = $this->getRequest();
        $id = $request->getParam('id') ? $request->getParam('id') : null;
        return $this->ruleFactory->create()
                    ->getCollection()->addFieldToSelect('*')
                    ->addFieldToFilter('company_id', ['eq' => $id])
                    ->addFieldToFilter('type', ['in' => [$filter]]);
    }

    /**
     * displayBlock
     *
     * @return filter_array $filter_array
     */
    public function displayBlock()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id') ? $request->getParam('id') : null;
        $acceptanceOption = '';
        $storeFrontMethod = '';
        if ($id !== null) {
            $company = $this->companyRepository->get((int) $id);
            $acceptanceOption = $company->getAcceptanceOption();
            $storeFrontMethod = $company->getStorefrontLoginMethodOption();
        }

        if ($acceptanceOption == 'both') {
            $filterArray = ['contact', 'extrinsic', $storeFrontMethod];
        } else {
            $filterArray = [$acceptanceOption, $storeFrontMethod];
        }
        return $filterArray;
    }
}
