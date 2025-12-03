<?php
declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\MassDelete as CatalogMassDelete;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Psr\Log\LoggerInterface;

class MassDelete
{
    /**
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CatalogMvp $catalogMvpHelper
     * @param ManagerInterface $messageManager
     * @param ResultFactory $resultFactory
     * @param ProductRepositoryInterface|null $productRepository
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        /**
         * Mass actions filter
         */
        protected Filter $filter,
        protected CollectionFactory $collectionFactory,
        protected CatalogMvp $catalogMvpHelper,
        protected ManagerInterface $messageManager,
        protected ResultFactory $resultFactory,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Show error message when legacy product delete
     * B-1642066 : RT-ECVS-Make legacy items non-editable and non-deletable
     *
     * @param CatalogMassDelete $subject
     * @param callable $proceed
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundExecute(
        CatalogMassDelete $subject,
        callable $proceed
    ) {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collection->addMediaGalleryData();

        $productDeleted = 0;
        $productDeletedError = 0;

        /*B-1642066*/
        $productLegacyError = 0;

        /** @var Product $product */
        foreach ($collection->getItems() as $product) {
            try {
                /*B-1642066*/
                $_product = $this->productRepository->get($product->getSku());
                $_isExternalProd = $_product->getExternalProd() ?? false;

                if (!$_isExternalProd) {
                    $this->productRepository->delete($_product);
                    $productDeleted++;
                } else {
                    $isLegacyProduct = $this->catalogMvpHelper->getIsLegacyItemBySku($product->getSku());
                    
                    if (!$isLegacyProduct) {
                        $this->productRepository->delete($_product);
                        $productDeleted++;
                    } else {
                        $productLegacyError++;
                    }
                }
            } catch (LocalizedException $exception) {
                $this->logger->error($exception->getLogMessage());
                $productDeletedError++;
            }
        }

        if ($productDeleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $productDeleted)
            );
        }

        if ($productDeletedError) {
            $this->messageManager->addErrorMessage(
                __(
                    'A total of %1 record(s) haven\'t been deleted. Please see server logs for more details.',
                    $productDeletedError
                )
            );
        }

        /*B-1642066*/
        if ($productLegacyError) {
            $this->messageManager->addErrorMessage(
                __(
                    'A total of %1 legacy item(s) haven\'t been deleted.',
                    $productLegacyError
                )
            );
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('catalog/*/index');
    }
}