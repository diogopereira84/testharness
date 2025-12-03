<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CatalogDocumentUserSettings\Helper;

use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection;
use Magento\Company\Model\CompanyFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\UserFactory;
use Magento\Catalog\Model\Layer\Resolver;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';

    /**
     * @var \Magento\Customer\Model\SessionFactory $customerSessionFactory
     */
    protected $_customerSessionFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Company\Model\CompanyFactory $companyFactory
     */
    protected $_companyFactory;

    /**
     * @var \Magento\Framework\Registry $registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\App\Request\Http $request
     */
    protected $_request;

    /**
     * @var \Magento\Catalog\Model\Config\Source\Category $category
     */
    protected $category;

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $_customerSession;

    /**
     * Data Class Constructor.
     *
     * @param Context $context
     * @param SessionFactory $customerSessionFactory
     * @param CompanyFactory $companyFactory
     * @param Registry $registry
     * @param Http $request
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param Category $categoryHelper
     * @param Collection $catalogPermission
     * @param Session $customerSession
     * @param UserFactory $userFactory
     * @param ToggleConfig $toggleConfig
     * @param AuthHelper $authHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        \Magento\Company\Model\CompanyFactory $companyFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Request\Http $request,
        protected \Magento\Store\Model\StoreManagerInterface $storeManager,
        private CategoryFactory $categoryFactory,
        protected \Magento\Catalog\Helper\Category $categoryHelper,
        protected \Magento\CatalogPermissions\Model\ResourceModel\Permission\Collection $catalogPermission,
        \Magento\Customer\Model\Session $customerSession,
        protected \Magento\User\Model\UserFactory $userFactory,
        protected \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig,
        protected AuthHelper $authHelper,
        private Resolver $layerResolver
    ) {
        parent::__construct($context);
        $this->_customerSessionFactory = $customerSessionFactory;
        $this->_companyFactory = $companyFactory;
        $this->_registry = $registry;
        $this->_request = $request;
        $this->_customerSession = $customerSession;
    }

    /**
     * Retrieve Company Configuration.
     *
     * @return Object.
     */
    public function getCompanyConfiguration()
    {
        $companyOb = $this->_companyFactory->create();
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $companyId = $this->getOrCreateCustomerSession()->getCustomerCompany();
        } else {
            $companyId = $this->_customerSessionFactory->create()->getCustomerCompany();
        }

        $companyConfiguartionData = $companyOb->load($companyId);

        return $companyConfiguartionData;
    }

    /**
     * Get Current Category
     *
     * @return Object
     */
    public function getCurrentCategory()
    {
        $toggleEnbleForPerformance = $this->toggleConfig->getToggleConfigValue('techtitan_performance_improment');
       if ($toggleEnbleForPerformance) {
        $category = $this->layerResolver->get()->getCurrentCategory();
       } else {
        $category = $this->_registry->registry('current_category');
       }
        return $category;
    }

    /**
     * Get Current Pagename
     *
     * @return Object
     */
    public function getActionName()
    {
        $currentPageactionName = trim((string)$this->_request->getFullActionName());

        return $currentPageactionName;
    }

    /**
     * Get Browse Catalog Link
     *
     * @return String
     */
    public function getBrowseCatalogLink()
    {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $rootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $catName = $this->categoryFactory->create()->load($rootCategoryId)->getName();
        $categories = [['value' => $rootCategoryId, 'label' => $catName]];
        $url = '';
        // check if customer is logged in
        if ($this->authHelper->isLoggedIn()) {
            $customerGroupId = $this->_customerSession->getCustomer()->getGroupId();
            foreach ($categories as $category) {
                if ($category['value']) {
                    $_categories = $this->categoryFactory->create()->getCategories($category['value']);
                    $url = $this->getCategoryUrl($_categories);
                }
            }
        }
        return $url;
    }

    /**
     * Get Print Product Link
     *
     * @return String
     */
    public function getPrintProductLink()
    {
        $storeId = $this->storeManager->getStore()->getStoreId();
        $rootCategoryId = $this->storeManager->getStore($storeId)->getRootCategoryId();
        $catName = $this->categoryFactory->create()->load($rootCategoryId)->getName();
        $categories = [['value' => $rootCategoryId, 'label' => $catName]];
        $url = '';
        if ($this->authHelper->isLoggedIn()) {
            $customerGroupId = $this->_customerSession->getCustomer()->getGroupId();
            $url = $this->getPrintProductUrl($categories, $url);
        }
        return $url;
    }

    /**
     * @param array $categories
     * @return string
     */
    public function getPrintProductUrl($categories, $url)
    {
        foreach ($categories as $category) {
            if ($category['value']) {
                $_categories = $this->categoryFactory->create()->getCategories($category['value']);
                if ($_categories) {
                    foreach ($_categories as $_category) {
                        if (strpos($_category->getName(), 'Print') !== false) {
                            $url = $this->categoryHelper->getCategoryUrl($_category);
                        }
                    }
                }
            }
        }
        return $url;
    }

    /**
     * Get Category Url
     *
     * @return String
     */
    public function getCategoryUrl($_categories)
    {
        if ($_categories) {
            foreach ($_categories as $_category) {
                if (strpos($_category->getName(), 'Browse') !== false) {
                    return $this->categoryHelper->getCategoryUrl($_category);
                }
            }
        }
    }

    /**
     * Get First Name
     *
     * @return String
     */
    public function getFirstName($userId)
    {
        $user = $this->userFactory->create()->load($userId);
        return $user->getFirstName();
    }

    /**
     * Toggle for Catalog Performance Improvement Phase Two
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->_customerSession->isLoggedIn()){
            $this->_customerSession = $this->_customerSessionFactory->create();
        }
        return $this->_customerSession;
    }

}
