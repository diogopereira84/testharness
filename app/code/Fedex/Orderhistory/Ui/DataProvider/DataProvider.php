<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * <!-- B-1053021 - Sanchit Bhatia - RT-ECVS - ePro - Search Capability for Quotes  -->
 */
namespace Fedex\Orderhistory\Ui\DataProvider;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\View\Element\UiComponent\DataProvider\Reporting;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\NegotiableQuote\Model\NegotiableQuote;
use Magento\NegotiableQuote\Model\Quote\Address;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;

/**
 * Class DataProvider
 * To modifiy serch resultes on Quote Page
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends \Magento\NegotiableQuote\Ui\DataProvider\DataProvider
{
    /**
     * @var \Magento\NegotiableQuote\Model\Quote\Address
     */
    private $negotiableQuoteAddress;

    /**
     * @var \Magento\NegotiableQuote\Model\NegotiableQuoteRepository
     */
    private $negotiableQuoteRepository;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Company\Model\Company\Structure
     */
    private $structure;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Company\Api\AuthorizationInterface
     */
    private $authorization;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Reporting $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param \Magento\NegotiableQuote\Model\NegotiableQuoteRepository $negotiableQuoteRepository
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param Address $negotiableQuoteAddress
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Company\Model\Company\Structure $structure
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Fedex\Orderhistory\Helper\Data $helper
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Reporting $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\RequestInterface $request,
        FilterBuilder $filterBuilder,
        \Magento\NegotiableQuote\Model\NegotiableQuoteRepository $negotiableQuoteRepository,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        Address $negotiableQuoteAddress,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Company\Model\Company\Structure $structure,
        \Magento\Company\Api\AuthorizationInterface $authorization,
        protected CustomerSession $customerSession,
        private \Fedex\Orderhistory\Helper\Data $helper,
        private TypeListInterface $cacheTypeList,
        private Pool $cacheFrontendPool,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $negotiableQuoteRepository,
            $userContext,
            $negotiableQuoteAddress,
            $storeManager,
            $structure,
            $authorization,
            $meta,
            $data
        );
        $this->negotiableQuoteRepository = $negotiableQuoteRepository;
        $this->userContext = $userContext;
        $this->negotiableQuoteAddress = $negotiableQuoteAddress;
        $this->storeManager = $storeManager;
        $this->structure = $structure;
        $this->request = $request;
        $this->authorization = $authorization;
    }

    /**
     * Returns Search result.
     *
     * @return SearchResultsInterface
     */
    public function getSearchResult()
    {
        $_types = [
            'layout',
            'block_html',
            'collections',
            'reflection',
            'full_page'
        ];

        foreach ($_types as $type) {
            $this->cacheTypeList->cleanType($type);
        }


        $this->addOrder('entity_id', 'DESC');
        $customerId = $this->getCustomerId();
        $allTeamIds = [];
        if ($this->authorization->isAllowed('Magento_NegotiableQuote::view_quotes_sub')) {
            $allTeamIds = $this->structure->getAllowedChildrenIds($customerId);
        }
        $allTeamIds[] = $customerId;
        $filter = $this->filterBuilder
            ->setField('main_table.customer_id')
            ->setConditionType('in')
            ->setValue(array_unique($allTeamIds))
            ->create();
        $this->searchCriteriaBuilder->addFilter($filter);
        $filter = $this->filterBuilder
            ->setField('store_id')
            ->setConditionType('in')
            ->setValue($this->storeManager->getStore()->getWebsite()->getStoreIds())
            ->create();
        $this->searchCriteriaBuilder->addFilter($filter);

        if ($this->helper->isModuleEnabled() == true) {
            /* B-1130766 */
            $searchdata = $this->customerSession->getSearchdata();
            $validData = $this->fetchSearhData($searchdata);

            if ($searchdata !== null && $validData['type'] == "numeric") {

				$searchEntityId = $validData['quote_id'];

				/** B-1096388 - Implement search through quote ID on the list of quote **/
                $filter = $this->filterBuilder
                ->setField('extension_attribute_negotiable_quote.quote_id')
                ->setConditionType('like')
                ->setValue('%'.$searchEntityId.'%')
                ->create();

                $this->searchCriteriaBuilder->addFilter($filter);
            }
            /* B-1094978 */
            $filter = $this->filterBuilder
                ->setField('status')
                ->setConditionType('in')
                ->setValue(['created','processing_by_admin',
                    'submitted_by_customer','submitted_by_admin'])
                ->create();

            $this->searchCriteriaBuilder->addFilter($filter);
        }

        $this->searchCriteria = $this->searchCriteriaBuilder->create();
        $this->searchCriteria->setRequestName($this->name);

        return $this->negotiableQuoteRepository->getList($this->getSearchCriteria(), true);
    }

    /**
     * Customer Id on Current Page
     *
     * @return int|null
     */
    private function getCustomerId()
    {
        return $this->userContext->getUserId() ? : null;
    }
    /* B-1130766 */
    public function fetchSearhData($str){
        $str = strtolower((string)$str);
        $splittedId = preg_split('/(?<=[0-9])(-)(?=[a-z]+)/i',$str);
        $splittedIdTwo = preg_split('/(?<=[0-9])(?=[a-z]+)/i',$str);
        $splittedIdThree = explode("-",$str);
        $isValid = false;
        $type = false;
        $quoteId = false;
        if(is_numeric($str)){
            $isValid = true;
            $type = "numeric";
            $quoteId = $str;
        }
        else if(count($splittedId) == 2 &&
         is_numeric(trim($splittedId[0])) &&
          ($splittedId[1] == "sepo" || $splittedId[1] == "sep" || $splittedId[1] == "se" || $splittedId[1] == "s")) {
            $isValid = true;
            $type = "numeric";
            $quoteId = trim($splittedId[0]);
        }
        else if(count($splittedIdThree) == 2 && is_numeric(trim($splittedIdThree[0])) && $splittedIdThree[1] == null) {
            $isValid = true;
            $type = "numeric";
            $quoteId = trim($splittedIdThree[0]);

        }
        else if($str == "-" || $str == "-sepo" || $str == "-sep" ||
         $str == "-se" || $str == "-s" || $str == "sepo" || $str == "sep" || $str == "se" || $str == "s") {
           $isValid = true;
           $type = "all";
        }else{
            $isValid = false;
            $type = "numeric";
            $quoteId = $str;
        }
        return ['type'=>$type,'quote_id'=>$quoteId];
    }
}
