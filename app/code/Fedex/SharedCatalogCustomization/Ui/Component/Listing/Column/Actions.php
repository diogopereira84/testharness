<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SharedCatalogCustomization\Ui\Component\Listing\Column;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SharedCatalogCustomization\Model\SharedCatalogSyncQueueConfigurationRepository;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\Form\Storage\UrlBuilder as StorageUrlBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Column actions.
 */
class Actions extends \Magento\SharedCatalog\Ui\Component\Listing\Column\Actions
{
    /** Url path */
    const SHARED_CATALOG_LEGACY_CATALOG_SYNC = 'shared_catalog_customization/manual/catalogsync';
    const SHARED_CATALOG_SYNC_CONFIGURATION = 'shared_catalog_customization/sharedcatalogsyncqueue/configuration';
    const SHARED_CATALOG_PRICE_SYNC = 'shared_catalog_customization/manual/catalogpricesync';

    /**
     * @var ContextInterface $context
     */
    protected $context;

    /**
     * @var UiComponentFactory $uiComponentFactory
     */
    protected $uiComponentFactory;

    /**
     * @var UrlInterface $urlBuilder
     */
    protected $urlBuilder;

    /**
     * @var SharedCatalogSyncQueueConfigurationFactory
     */
    protected $sharedCatalogConfigFactory;

    /**
     * @var Escaper $escaper
     */
    private $escaper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository
     * @param LoggerInterface $logger
     * @param array $components
     * @param array $data
     * @param Escaper|null $escaper
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        private SharedCatalogSyncQueueConfigurationRepository $sharedCatalogConfRepository,
        private CatalogMvp $catalogMvpHelper,
        private LoggerInterface $logger,
        array $components = [],
        array $data = [],
        Escaper $escaper = null
    ) {
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(Escaper::class);
    }
    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[SharedCatalogInterface::SHARED_CATALOG_ID])) {
                    $this->prepareDataSourceItem($item);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Prepare data source item.
     *
     * @param array $item
     *
     * @return void
     */
    protected function prepareDataSourceItem(&$item)
    {
        $sharedCatalogId = $item[SharedCatalogInterface::SHARED_CATALOG_ID];
        $sharedCatalogName = $item[SharedCatalogInterface::NAME];
        $customerGroupId = $item[SharedCatalogInterface::CUSTOMER_GROUP_ID];
        
        /*behalf of shared catalog id we will get from new table legacy root  id,
        category id  and status  load filer sha*/
        $categoryId = null;
        $legacyCatalogRootFolderId = null;
        
        try {
            $sharedCatalogConfData = $this->sharedCatalogConfRepository->getBySharedCatalogId($sharedCatalogId);
            $categoryId = $sharedCatalogConfData->getStatus() ?
                                            $sharedCatalogConfData->getCategoryId() : null;
            $legacyCatalogRootFolderId  = $sharedCatalogConfData->getStatus() ?
                                            $sharedCatalogConfData->getData('legacy_catalog_root_folder_id') : null;
            $this->logger->info(__METHOD__.':'.__LINE__.':Category '.$categoryId.' retrieved.');

        } catch (NoSuchEntityException $exception) {
            $this->logger->error(__METHOD__.':'.__LINE__.':Catalog sync error with category fetch: ' . $exception->getMessage());
        }

        $item[$this->getData('name')] = [
            'configure' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_INDEX_CONFIGURE,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId,
                        StorageUrlBuilder::REQUEST_PARAM_CONFIGURE_KEY => $this->getConfigureKey($item),
                    ]
                ),
                'label' => __('Set Pricing and Structure'),
            ],
            'companies' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_INDEX_COMPANIES,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId,
                        StorageUrlBuilder::REQUEST_PARAM_CONFIGURE_KEY => $this->getConfigureKey($item),
                    ]
                ),
                'label' => __('Assign Companies'),
            ],
            'legacycatalogsync' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_LEGACY_CATALOG_SYNC,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId,
                        SharedCatalogInterface::NAME => $sharedCatalogName,
                        SharedCatalogInterface::CUSTOMER_GROUP_ID => $customerGroupId,
                        'legacy_catalog_root_folder_id' => $legacyCatalogRootFolderId,
                        'category_id' => $categoryId

                    ]
                ),
                'label' => __('Legacy Catalog Sync'),
            ],
            'sharedcatalogsyncconfig' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_SYNC_CONFIGURATION,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId
                    ]
                ),
                'label' => __('Shared Catalog Sync Configuration'),
            ],
            'catalogpricesync' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_PRICE_SYNC,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId,
                        SharedCatalogInterface::NAME => $sharedCatalogName,
                        SharedCatalogInterface::CUSTOMER_GROUP_ID => $customerGroupId,                       
                        'category_id' => $categoryId
                    ]
                ),
                'label' => __('Shared Catalog Price Sync'),
            ],
            
            'edit' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_INDEX_EDIT,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId,
                    ]
                ),
                'label' => __('General Settings'),
            ],
            'delete' => [
                'href' => $this->urlBuilder->getUrl(
                    self::SHARED_CATALOG_INDEX_DELETE,
                    [
                        SharedCatalogInterface::SHARED_CATALOG_ID_URL_PARAM => $sharedCatalogId,
                    ]
                ),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete "%1"', $this->escaper->escapeHtml($item[SharedCatalogInterface::NAME])),
                    'message' => __('This action cannot be undone. Are you sure you want to delete this catalog?'),
                ],
                'post' => true,
            ],
        ];
       
        if(!$this->catalogMvpHelper->isMvpCtcAdminEnable()){
            unset($item[$this->getData('name')]['catalogpricesync']);
        }

    }
}
