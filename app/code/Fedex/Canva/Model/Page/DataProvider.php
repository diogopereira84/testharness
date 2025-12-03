<?php
/**
 * @category Fedex
 * @package  Fedex_Canva
 * @copyright   Copyright (c) 2023 Fedex
 * @author    Iago Lima <iago.lima.osv@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Canva\Model\Page;

use Fedex\Canva\Model\Builder;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page\CustomLayoutManagerInterface;
use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Psr\Log\LoggerInterface;

class DataProvider extends \Magento\Cms\Model\Page\DataProvider
{
    private const CANVA_SIZES = 'canva_sizes';
    private const DEFAULT = 'default';

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $pageCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param SerializerInterface $serializer
     * @param Builder $builder
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     * @param AuthorizationInterface|null $auth
     * @param RequestInterface|null $request
     * @param CustomLayoutManagerInterface|null $customLayoutManager
     * @param PageRepositoryInterface|null $pageRepository
     * @param PageFactory|null $pageFactory
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $pageCollectionFactory,
        DataPersistorInterface $dataPersistor,
        protected SerializerInterface $serializer,
        protected Builder $builder,
        protected ToggleConfig $toggleConfig,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null,
        ?AuthorizationInterface $auth = null,
        ?RequestInterface $request = null,
        ?CustomLayoutManagerInterface $customLayoutManager = null,
        ?PageRepositoryInterface $pageRepository = null,
        ?PageFactory $pageFactory = null,
        ?LoggerInterface $logger = null
    ){
//NOSONAR
        parent::__construct($name, $primaryFieldName, $requestFieldName, $pageCollectionFactory, $dataPersistor, $meta, $data, $pool, $auth, $request, $customLayoutManager, $pageRepository, $pageFactory, $logger);
        $this->pageRepository = $pageRepository ?? ObjectManager::getInstance()->get(PageRepositoryInterface::class);
        $this->pageFactory = $pageFactory ?: ObjectManager::getInstance()->get(PageFactory::class);
        $this->request = $request ?? ObjectManager::getInstance()->get(RequestInterface::class);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        if (!$this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')) {
            if (isset($this->loadedData)) {
                return $this->loadedData;
            }
            $page = $this->getCurrentPage();
            $this->loadedData[$page->getId()] = $page->getData();
            if ($page->getCustomLayoutUpdateXml() || $page->getLayoutUpdateXml()) {
                //Deprecated layout update exists.
                $this->loadedData[$page->getId()]['layout_update_selected'] = '_existing_';
            }
            foreach ($this->loadedData as $key => $item) {
                $this->loadedData[$key][self::CANVA_SIZES] = $item[self::CANVA_SIZES] ?? [];
                $this->loadedData[$key][self::DEFAULT] = $item[self::DEFAULT] ?? 'option_0';
                if (is_string($this->loadedData[$key][self::CANVA_SIZES])) {
                    $collection = $this->builder->build($this->serializer->unserialize($item[self::CANVA_SIZES]));
                    $this->loadedData[$key][self::CANVA_SIZES] = $collection->toArray();
                    $this->loadedData[$key][self::DEFAULT] = $collection->getDefaultOptionId();
                }
            }
            return $this->loadedData;
        } else {
            return parent::getData();
        }
    }

    /**
     * Return current page
     *
     * @return PageInterface
     * @codeCoverageIgnore
     */
    private function getCurrentPage(): PageInterface
    {
        $pageId = $this->getPageId();
        if ($pageId) {
            try {
                $page = $this->pageRepository->getById($pageId);
            } catch (LocalizedException $exception) {
                $page = $this->pageFactory->create();
            }

            return $page;
        }

        $data = $this->dataPersistor->get('cms_page');
        if (empty($data)) {
            return $this->pageFactory->create();
        }
        $this->dataPersistor->clear('cms_page');

        return $this->pageFactory->create()
            ->setData($data);
    }

    /**
     * Returns current page id from request
     *
     * @return int
     * @codeCoverageIgnore
     */
    private function getPageId(): int
    {
        return (int) $this->request->getParam($this->getRequestFieldName());
    }
}
