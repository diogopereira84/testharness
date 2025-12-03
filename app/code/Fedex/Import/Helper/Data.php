<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Helper;

use Fedex\Import\Model\Source\Factory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\SharedCatalog\Model\ResourceModel\SharedCatalog\CollectionFactory;
use \Psr\Log\LoggerInterface;

/**
 * Helper Class Data
 */
class Data extends AbstractHelper
{
    protected const GENERAL_DEBUG = 'fedex_import/general/debug';

    /**
     * @var ScopeConfigInterface
     */
    protected $coreConfig;

    /**
     * Data Helper constructor
     *
     * @param Context $context
     * @param Factory $sourceFactory
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $configInterface
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        /**
         * Import source type factory model
         */
        protected Factory $sourceFactory,
        private EncryptorInterface $encryptor,
        private StoreManagerInterface $storeManager,
        private ScopeConfigInterface $configInterface,
        protected CollectionFactory $collectionFactory,
        protected LoggerInterface $logger
    ) {
        $this->coreConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Prepare source type class name
     *
     * @param string $sourceType
     *
     * @return string
     */
    protected function prepareSourceClassName($sourceType)
    {
        return 'Fedex\Import\Model\Source\Type\\' . ucfirst(strtolower($sourceType));
    }

    /**
     * Get source model by source type
     *
     * @param string $sourceType
     *
     * @return \Fedex\Import\Model\Source\Type\AbstractType
     * @throws LocalizedException
     */
    public function getSourceModelByType($sourceType)
    {
        $sourceClassName = $this->prepareSourceClassName($sourceType);
        if ($sourceClassName && class_exists($sourceClassName)) {
            return $this->getSourceFactory()->create($sourceClassName);
        } else {
            $this->logger->error(
                __METHOD__.':'.__LINE__.' Import source type class for '.$sourceType.' does not exist'
            );
            throw new LocalizedException(
                __("Import source type class for '" . $sourceType . "' is not exist.")
            );
        }
    }

    /**
     * Get source factory
     *
     * @return Factory
     */
    public function getSourceFactory()
    {
        return $this->sourceFactory;
    }

    /**
     * Get Debug Mode
     *
     * @return bool
     */
    public function getDebugMode()
    {
        return (bool)$this->coreConfig->getValue(
            self::GENERAL_DEBUG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get admin Token
     *
     * @return json
     */
    public function getAdminToken()
    {
        $dataString=$this->getApiUserCredentials();
        $dataString=json_encode($dataString);
        $setupURL= $this->getBaseUrl()."rest/V1/integration/admin/token";
        $headers=[
            "Content-Type: application/json","Accept: application/json","Accept-Language: json","Content-Length: ".
            strlen($dataString)
        ];

        $ch = curl_init($setupURL);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' Error getting admin token.');
        } else {

            $response = curl_getinfo($ch);
            curl_close($ch);

            if ($response['http_code']==200) {
                return json_decode($result, true);
            }
        }
    }

    /**
     * Get API user credentials
     *
     * @return bool
     */
    public function getApiUserCredentials()
    {
        $username= $this->configInterface->getValue("fedex/authentication/username");
        $password = $this->configInterface->getValue("fedex/authentication/password");
        $stringPassword=$this->encryptor->decrypt($password);
        return [
            'username' => $username,
            'password' => $stringPassword,
        ];
    }

    /**
     * Get base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get by name
     *
     * @param string $sharedCatalog
     * @return array
     */
    public function getByName($sharedCatalog)
    {

        $sharedCatalogCollection = $this->collectionFactory->create();
        $sharedCatalogDatas = $sharedCatalogCollection
        ->addFieldToFilter('name', ['eq' => $sharedCatalog])->load()->getData();
        if ($sharedCatalogDatas) {
            foreach ($sharedCatalogDatas as $key => $sharedCatalogData) {

                $id = $sharedCatalogData['entity_id'];

            }
        }
        return $sharedCatalogDatas?$id:$sharedCatalogDatas;
    }
}
