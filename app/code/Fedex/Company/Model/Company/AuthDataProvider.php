<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Model\Company;

use Magento\Company\Api\Data\CompanyInterface;

/**
 * Data provider for company.
 */
class AuthDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    /**
     * Get company general data.
     *
     * @param \Magento\Company\Api\Data\CompanyInterface $company
     * @return array
     */
    const DATA_SCOPE_AUTHENTICATION = 'authentication_rule';

    protected $loadedData;
    protected $rowCollection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Fedex\Company\Model\ResourceModel\AuthDynamicRows\CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->rowCollection = $collectionFactory;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @Athor Pratibha
     * load data
     * @param
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $collection = $this->rowCollection->create();
        $items = $collection->getItems();
        foreach ($items as $item) {
            $this->loadedData['stores']['dynamic_rows_container'][] = $item->getData();
        }
        return $this->loadedData;
    }
    /**
     * @Athor Pratibha
     * get company auth data
     * @param $company
     * @return array
     */
    public function getAuthenticationData(CompanyInterface $company)
    {
        return [
            'acceptance_option' => $company->getAcceptanceOption(),
        ];
    }
}
