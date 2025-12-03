<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Model\Grid;

use Fedex\CIDPSG\Model\ResourceModel\Customer\Collection;
use Fedex\CIDPSG\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;

/**
 * Model Class DataProvider
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var $loadedData
     */
    protected $loadedData;

    /**
     * @var String $name
     */
    protected $name;

    /**
     * @var string $primaryFieldName
     */
    protected $primaryFieldName;

    /**
     * @var string $requestFieldName
     */
    protected $requestFieldName;

    /**
     * @var CollectionFactory $collectionFactory
     */
    protected $collectionFactory;

    /**
     * @var array $meta
     */
    protected $meta;

    /**
     * @var array $data
     */
    protected $data;
    private CollectionFactory $rowCollection;

    /**
     * Initialize dependencies
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param Collection $collection
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Collection $collection,
        CollectionFactory $collectionFactory,
        protected RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->rowCollection = $collectionFactory;
        $this->collection = $collection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get Data of PSG Customer form
     *
     * @return array
     */
    public function getData()
    {
        $id = $this->request->getParam('entity_id');
        $items = $this->rowCollection->create();
        $items->getSelect()->joinLeft(
            ["pcf" => $this->collection->getTable("psg_customer_fields")],
            'main_table.entity_id = pcf.psg_customer_entity_id',
        );
        if ($id) {
            $items->addFieldToFilter('main_table.entity_id', ['eq = ?' => $id]);
        } else {
            $items->addFieldToFilter('main_table.client_id', ['eq = ?' => 'default']);
        }
        $customerData = $items->getItems();
        $this->loadedData = ['form' => []];

        if (!$id) {
            $this->loadedData['form']['client_id'] = $this->getAutoGenerateClientId();
        }

        foreach ($customerData as $customerModel) {
            if ($id) {
                $this->loadedData['form']['entity_id'] = $id;
                $this->loadedData['form']['client_id'] = $customerModel->getClientId();
                $this->loadedData['form']['company_participation_id'] = $customerModel->getCompanyParticipationId();
                $this->loadedData['form']['company_name'] = $customerModel->getCompanyName();
                $this->loadedData['form']['participation_agreement'] = $customerModel->getParticipationAgreement();
                $this->loadedData['form']['support_account_type'] = $customerModel->getSupportAccountType();
                if ($customerModel->getData('company_logo')
                && strtolower($customerModel->getData('company_logo')) != 'null') {
                    $mediaData[0]= json_decode($customerModel->getData('company_logo'), true);
                    $this->loadedData['form']['company_logo'] = $mediaData;
                }
            }
            $postion = $customerModel->getPosition();
            $arrField = [
                'field_label' => $customerModel->getFieldLabel(),
                'field_description' => $customerModel->getFieldDescription(),
                'validation_type' => $customerModel->getValidationType(),
                'max_character_length' => $customerModel->getMaxCharacterLength(),
                'is_required' => $customerModel->getIsRequired(),
                'position' => $postion
            ];
            if ($customerModel->getData('field_group') == '0') {
                $this->loadedData['form']['default_fields'][$postion] = $arrField;
            } elseif ($customerModel->getData('field_group') == '1' && $id) {
                $this->loadedData['form']['custom_fields'][$postion] = $arrField;
            }
        }
        if (isset($this->loadedData['form']['default_fields'])) {
            ksort($this->loadedData['form']['default_fields']);
            array_unshift($this->loadedData['form']['default_fields']);
        }
        if (isset($this->loadedData['form']['custom_fields'])) {
            ksort($this->loadedData['form']['custom_fields']);
            array_unshift($this->loadedData['form']['custom_fields']);
        }

        return $this->loadedData;
    }

    /**
     * Get client id autogenerate for PSG customer
     *
     * @return string
     */
    public function getAutoGenerateClientId()
    {
        $data = random_bytes(10);
        $data[6] = chr(ord($data[6])&0x0f | 0x40);
        $data[4] = chr(ord($data[4])&0x3f | 0x80);

        return vsprintf('%s%s-%s-%s', str_split(bin2hex($data), 2));
    }
}
