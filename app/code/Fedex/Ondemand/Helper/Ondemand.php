<?php

namespace Fedex\Ondemand\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Company\Model\CompanyFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Framework\App\Request\Http;
use Magento\Catalog\Model\CategoryFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Base\Helper\Auth as AuthHelper;

class Ondemand extends AbstractHelper
{
    /**
     * Data Class Constructor.
     *
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param UrlInterface $urlInterface
     * @param CompanyFactory $companyFactory
     * @param SelfReg $selfReg
     * @param StoreFactory $storeFactory
     * @param SessionFactory $sessionFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param DeliveryHelper $deliveryHelper
     * @param CompanyManagementInterface $companyRepository
     * @param Http $requestHttp
     * @param CategoryFactory $categoryFactory
     * @param CatalogMvp $catalogMvpHelper
     * @param AuthHelper $authHelper
     */
    public function __construct(
        Context                                  $context,
        protected ToggleConfig                   $toggleConfig,
        protected UrlInterface                   $urlInterface,
        protected CompanyFactory                 $companyFactory,
        protected SelfReg                        $selfReg,
        private StoreFactory                     $storeFactory,
        protected SessionFactory                 $sessionFactory,
        protected ScopeConfigInterface           $scopeConfigInterface,
        protected DeliveryHelper                 $deliveryHelper,
        protected CompanyManagementInterface     $companyRepository,
        private Http                             $requestHttp,
        protected CategoryFactory                $categoryFactory,
        protected CatalogMvp                     $catalogMvpHelper,
        protected AuthHelper                     $authHelper,
        private readonly OndemandConfigInterface $ondemandConfig
    ) {
        parent::__construct($context);
    }

    /**
     * return boolean
     */

    public function getCompanyFromUrlExtension($urlExtension)
    {
        $companyObj = $this->companyFactory->create()
            ->getCollection()
            ->addFieldToFilter('company_url_extention', ['eq' => $urlExtension])->getFirstItem();
        if ($companyObj && is_array($companyObj->getData())) {
            return $companyObj->getData();
        }
        return false;
    }

    public function getOndemandStoreUrl()
    {
		return $this->storeFactory->create()->load('ondemand', 'code')->getUrl();
    }

    public function getOndemandCompanyData()
    {
            $urlExtension = $this->requestHttp->getParam('url');
            if(!$urlExtension){
				$returnData =  ['ondemand_url' => true, 'url_extension' => false];
			}else{
				$companyData = $this->getCompanyFromUrlExtension($urlExtension);
				if ($companyData && isset($companyData['entity_id'])) {
					$companyId = $companyData['entity_id'];
					$returnData['company_id'] = $companyId;
					$returnData['company_data'] = $companyData;
					$returnData['ondemand_url'] = true;
					$returnData['url_extension'] = true;
					if ($companyData['is_sensitive_data_enabled']) {
						$returnData['company_type'] = 'sde';
					} elseif ($this->selfReg->checkSelfRegEnable($companyId)) {
						$returnData['company_type'] = 'selfreg';
					} else {
						$returnData['company_type'] = 'epro';
					}
				}else{
					$returnData =  ['ondemand_url' => true, 'url_extension' => false];
				}
			}
            return $returnData;
    }

    public function getPrintProductCategory($child, $childLevel)
    {
        $returnValue = true;
        if ($childLevel == "0") {
            $categoryName = strtolower($child->getName());
            if ($categoryName === "browse catalog" || (strpos($categoryName, "browse catalog") !== false)) {
                $returnValue = true;
            } else {
                $categoryId = $child->getId();
                $categoryId = str_replace("category-node-","",$categoryId);
                $type = $this->getCustomerTypeFromSession();
                $field = $type."_print";
                $printCatId = $this->scopeConfigInterface->getValue(
                    'ondemand_setting/category_setting/'.$field,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                if ($categoryId == $printCatId) {
                    $returnValue = true;
                } else {
                    $returnValue = false;
                }
            }
        }
        return $returnValue;
    }

    /*B-1598909 : RT-ECVS-Feedback-Do not show Print Product Category, if product not available*/
    /**
     * @param $child
     * @param $childLevel
     * @return bool
     */
    public function isProductAvailable($child, $childLevel): bool
    {
        $returnValue = true;
        if($childLevel > 0){

            $categoryId = $child->getId();
            $categoryId = str_replace("category-node-","",$categoryId);
            if(!$this->isPrintProductCategory($categoryId)) {
                return true;
            }
            $allcategoryproduct = $this->categoryFactory->create()->load($categoryId)
                ->getProductCollection()
                ->addAttributeToSelect('*');

            $count = $allcategoryproduct->count();
            if ($count > 0) {
                $returnValue = true;
            } else {
                $returnValue = false;
            }
        }
        return $returnValue;
    }

    public function isPublishCategory($child, $childLevel): bool
    {

        if($this->catalogMvpHelper->isMvpSharedCatalogEnable() && $childLevel > 0 && !$this->catalogMvpHelper->isSelfRegCustomerAdmin()) {
            $categoryId = $child->getId();
            $categoryId = str_replace("category-node-", "", $categoryId);
            $category = $this->categoryFactory->create()->load($categoryId);

            if ($this->ondemandConfig->isTigerD239305ToggleEnabled()) {
                if (!$category) {
                    return true;
                }
                $globalCategories = $this->ondemandConfig->getGlobalB2BCategories();
                $parentCategories = explode("/", $category->getPath());
                if (array_intersect($globalCategories, $parentCategories)) {
                    return true;
                }
                return (bool) $category->getIsPublish();

            } else {
                $printCatId = $this->scopeConfigInterface->getValue(
                    'ondemand_setting/category_setting/epro_print',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                if ($category) {
                    $parentCategories = explode("/", $category->getPath());
                    if (in_array($printCatId, $parentCategories)) {
                        return true;
                    }
                    return ($category->getIsPublish() != null) ? $category->getIsPublish() : true;
                }
            }

        }
        return true;
    }
    public function isPrintProductCategory($categoryId) {
        if($categoryId) {
            $printCatId = $this->scopeConfigInterface->getValue(
                'ondemand_setting/category_setting/epro_print',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $category = $this->categoryFactory->create()->load($categoryId);
            $parentCategories = explode("/",$category->getPath());
            if (in_array($printCatId, $parentCategories)) {
                return true;
            }
        }
        return false;
    }
    public function getCustomerTypeFromSession()
    {
        $type = "";
        $customerSession = $this->sessionFactory->create();
        if ($this->authHelper->isLoggedIn()) {
            $companyData = $this->companyRepository->getByCustomerId($customerSession->getId());
            if ($companyData) {
                $companyId = $companyData->getId();
                $customerSession->setCustomerCompany($companyId);
                $companyObj = $this->companyFactory->create()->load($companyId);
                if ($companyObj->getData('is_sensitive_data_enabled')) {
                    $type = "sde";
                } elseif ($this->selfReg->checkSelfRegEnable($companyId)) {
                    $type = "selfreg";
                } elseif ($this->deliveryHelper->isCommercialCustomer()) {
                    $type = "epro";
                }
            }
        }
        return $type;
    }
}
