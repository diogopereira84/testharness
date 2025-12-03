<?php
namespace Fedex\CmsImportExport\Block\Adminhtml;

use Psr\Log\LoggerInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\PageBuilder\Model\ResourceModel\Template\Grid\CollectionFactory as PageBuilderCollectionFactory;
use Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;

class CmsData extends \Magento\Framework\View\Element\Template
{
    public $collection;

    /**
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Cms\Api\PageRepositoryInterface $pageRepositoryInterface
     * @param \Magento\Cms\Api\BlockRepositoryInterface $blockRepositoryInterface
     * @param \Magento\PageBuilder\Model\ResourceModel\Template\Grid\CollectionFactory $pagebuilderCollection
     * @param \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $instanceCollection
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */

    public function __construct(
        Context $context,
        public PageRepositoryInterface $pageRepositoryInterface,
        public BlockRepositoryInterface $blockRepositoryInterface,
        PageBuilderCollectionFactory $pagebuilderCollection,
        public CollectionFactory $instanceCollection,
        public SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        protected ManagerInterface $messageManager,
        protected LoggerInterface $logger,
        array $data = []
    ) {
        $this->collection = $pagebuilderCollection;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $data);
    }
 
    /* Get Pages Collection from site. */
    public function getPages()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->pageRepositoryInterface->getList($searchCriteria)->getItems();
    }

    /* Get Block Collection from site. */
    public function getBlocks()
    {
        $searchCriteria =  $this->searchCriteriaBuilder->create();
        return $this->blockRepositoryInterface->getList($searchCriteria)->getItems();
    }
 
    /* Get Page Template Collection from site. */
    public function getPageTemplate()
    {
        return $this->collection->create();
    }

    /* Get Widget Instance from site. */
    public function getInstanceWidget()
    {
        return $this->instanceCollection->create();
    }

    public function exportSuccessMessage()
    {
        return $this->messageManager->addSuccess(__(" are export successfully"));
    }

    public function exportErrorMessage()
    {
        $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Export was unsuccessful.');
        return $this->messageManager->addError(__(" export was unsuccessful."));
    }
       
 
    /**
     * Return the Url for controller.
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->_urlBuilder->getUrl('importexportcms/cmsexport/export/');
    }
}
