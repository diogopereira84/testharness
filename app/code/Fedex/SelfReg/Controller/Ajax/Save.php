<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Controller\Ajax;

use Exception;
use Fedex\SaaSCommon\Api\ConfigInterface as FedexSaaSCommonConfig;
use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Fedex\SelfReg\Model\CategoryPermissionProcessor;
use Magento\SharedCatalog\Model\State;

class Save implements HttpPostActionInterface
{

    /**
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param CategoryPermissionProcessor $categoryPermissionProcessor
     * @param State $sharedCatalogState
     * @param FedexSaaSCommonConfig $fedexSaaSCommonConfig
     * @param CustomerGroupAttributeHandlerInterface $customerGroupAttributeHandler
     */
    public function __construct(
        private JsonFactory $resultJsonFactory,
        private RequestInterface $request,
        private LoggerInterface $logger,
        private CategoryPermissionProcessor $categoryPermissionProcessor,
        private State $sharedCatalogState,
        private FedexSaaSCommonConfig                   $fedexSaaSCommonConfig,
        private CustomerGroupAttributeHandlerInterface  $customerGroupAttributeHandler,
    ) {}

    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $fileName = basename(__FILE__);

        try {
            $postData = $this->request->getParams() ?? [];

            if (!empty($postData)) {
                $selectedGroupIds = $postData['groupIds'] ?? [];
                $categoryId = $postData['categoryId'] ?? '';
                $isFolderRestricted = (bool) $postData['isFolderRestricted'] ?? false;
                try {
                    if (!empty($categoryId) ) {

                        $activeWebsites = $this->categoryPermissionProcessor->getActiveWebsiteIds();
                        $permissionWebsiteIds = $this->sharedCatalogState->isGlobal() ? [null] :  $activeWebsites;

                        if ($permissionWebsiteIds === [null]) {
                            $this->categoryPermissionProcessor->processPermissions(
                                null,
                                (int) $categoryId,
                                $isFolderRestricted,
                                $selectedGroupIds
                            );
                        } else {

                            foreach ($permissionWebsiteIds as $scopeId) {
                                $this->categoryPermissionProcessor->processPermissions(
                                    $scopeId,
                                    (int) $categoryId,
                                    $isFolderRestricted,
                                    $selectedGroupIds
                                );
                            }
                        }

                        if ($this->fedexSaaSCommonConfig->isTigerD200529Enabled()) {
                            $this->customerGroupAttributeHandler->pushEntityToQueue(
                                (int)$categoryId,
                                Category::ENTITY
                            );
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->error(
                        "[$fileName] Method: " . __METHOD__ . " Line: " . __LINE__ . 
                        " - Failed to set permissions for category ID $categoryId: " . $e->getMessage());
                }

                $resultJson->setData([
                    'status' => 'success',
                    'message' => __('Category Permissions saved successfully.')
                ]);
            }
            }catch (Exception $e) {
            $this->logger->error(
                "[$fileName] Method: " . __METHOD__ . " Line: " . __LINE__ . 
                " - Error in execute method: " . $e->getMessage());
            $resultJson->setData([
                'status' => 'error',
                'message' => __('An error occurred while saving permissions.')
            ]);
        }

            return $resultJson;
    }
}
