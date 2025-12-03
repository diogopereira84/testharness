<?php

namespace Fedex\Company\Block\Adminhtml;

use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\Shipto\Model\ProductionLocationFactory;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class ProductionLocation extends \Magento\Backend\Block\Template
{
    private const COMPANY_ID = 'company_id';
    private const RECOMMENDED_STORE = 'is_recommended_store';
    private const RADIUS = '15';
    /**
     * Block template
     *
     * @var string
     */
    protected $_template = 'production_location.phtml';

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        /**
         * @var $companyRepository Magento\Company\Api\CompanyRepositoryInterface
         */
        protected CompanyRepositoryInterface $companyRepository,
        /**
         * @var $productionlocationFactory Fedex\Shipto\Model\ProductionLocationFactory
         */
        protected ProductionLocationFactory $productionlocationFactory,
        protected ToggleConfig $toggleConfig
    ) {
        parent::__construct($context);
    }

    /**
     * displayBlock
     *
     * @return boolean
     */
    public function displayBlock()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id') ? $request->getParam('id') : null;
        $allowProductionLocations = false;
        if ($id !== null) {
            $company = $this->companyRepository->get((int) $id);
            $allowProductionLocations = $company->getAllowProductionLocation();
        }
        return $allowProductionLocations;
    }

    /**
     * getCompanyData
     * @return object
     */
    public function getCompanyData()
    {
        $request = $this->getRequest();
        $id = $request->getParam('id') ? $request->getParam('id') : null;
        $company = null;
        if ($id !== null) {
            $company = $this->companyRepository->get((int) $id);
        }
        return $company;
    }

    /**
     * isLocationUiFixToggle
     * @return boolean
     */
    public function isLocationUiFixToggle()
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_d_191726_fix');
    }

    /**
     * Get production locations based on company ID and recommendation status.
     *
     * @param int $id The company ID.
     * @param int $isRecommended Flag to filter recommended (1) or not recommended (0) locations. Defaults to 0.
     * @return array Collection data of production locations.
     */
    public function getProductionLocationsData($companyId, $isRecommended = 0 )
    {
        $productionLocation = $this->productionlocationFactory->create();
        $collection = $productionLocation->getCollection()
            ->addFieldToFilter(self::COMPANY_ID,$companyId)
            ->addFieldToFilter(self::RECOMMENDED_STORE, ['eq' => $isRecommended]);
        return $collection->getData();
    }

    /**
     * Get toggle config value
     *
     * @return boolean
     */
    public function isRestrictedProductionToggleEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('explorers_restricted_and_recommended_production');
    }

    
    /**
     * Get default radious
     *
     * @return boolean
     */
    public function getRadius()
    {
        return self::RADIUS;
    }
}
