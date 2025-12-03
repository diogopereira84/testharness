<?php
declare(strict_types=1);

namespace Fedex\Company\Model\Queue;

use Fedex\Company\Api\CreateCompanyEntitiesMessageInterface;
use Fedex\Company\Model\CompanyCreation;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class CreateCompanyEntitiesConsumer
{

    /**
     * @param RequestInterface $request
     * @param Json $serializer
     * @param LoggerInterface $logger
     * @param EventManager $eventManager
     * @param CompanyCreation $companyCreation
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        protected RequestInterface $request,
        protected Json $serializer,
        protected LoggerInterface $logger,
        protected EventManager $eventManager,
        protected CompanyCreation $companyCreation,
        protected CompanyRepositoryInterface $companyRepository,
    ){}

    /**
     * @param $message
     * @return void
     */
    public function initializeCompanyExtraEntitiesCreation(CreateCompanyEntitiesMessageInterface $message)
    {
        $companyRequestEntities = $this->serializer->unserialize($message->getMessage());
        try {
            $companyId = $companyRequestEntities['company_id'];
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Starting entities for Company:'.$companyId);
            $company = $this->loadCompany($companyId);
            $urlExtensionName = $companyRequestEntities['url_extension_name'];
            $this->request->setParams($companyRequestEntities['request_params']);

            switch ($companyRequestEntities['creation_type']) {

                case 'all':
                    $this->companyCreation->initializeCompanyExtraEntitiesCreation($urlExtensionName);
                    $createdCustomerGroup = $this->companyCreation->getCreatedCustomerGroup();
                    $createdRootCategory = $this->companyCreation->getCreatedRootCategory();
                    $company->setCustomerGroupId($createdCustomerGroup->getId());
                    $company->setSharedCatalogId($createdRootCategory->getId());
                    $this->logger->info(__METHOD__.':'.__LINE__.' Company:'.$companyId.' GroupId:'.$createdCustomerGroup->getId().' SharedCatalogId:'.$createdRootCategory->getId());
                    break;

                case 'root_category':
                    $customerGroupId = $companyRequestEntities['customer_group_id'];
                    $this->companyCreation->initializeOnlyRootCategoryCreation(
                        $urlExtensionName,
                        $customerGroupId
                    );
                    $createdRootCategory = $this->companyCreation->getCreatedRootCategory();
                    $company->setSharedCatalogId($createdRootCategory->getId());
                    $this->logger->info(__METHOD__.':'.__LINE__.' Company:'.$companyId.' SharedCatalogId:'.$createdRootCategory->getId());
                    break;

                case 'customer_group':
                    $sharedCatalogId = $companyRequestEntities['shared_catalog_id'];
                    $this->companyCreation->initializeOnlyCustomerGroupCreation(
                        $urlExtensionName,
                        $sharedCatalogId
                    );
                    $createdCustomerGroup = $this->companyCreation->getCreatedCustomerGroup();
                    $company->setCustomerGroupId($createdCustomerGroup->getId());
                    $this->logger->info(__METHOD__.':'.__LINE__.' Company:'.$companyId.' GroupId:'.$createdCustomerGroup->getId());
                    break;

                default;
                    break;
            }
            $this->companyRepository->save($company);
            $this->triggerModelSaveEvent($company);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Finish entities for Company:'.$companyId);
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' Failed to generate entities for Company:'.$companyRequestEntities['company_id']);
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' ' . $e->getTraceAsString());
        }
    }

    /**
     * @param $companyId
     * @return CompanyInterface
     * @throws NoSuchEntityException
     */
    protected function loadCompany($companyId): CompanyInterface
    {
        try {
            return $this->companyRepository->get((int)$companyId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
            throw new NoSuchEntityException(__($e->getMessage()));
        }
    }

    /**
     * @param $company
     * @return void
     */
    protected function triggerModelSaveEvent($company): void
    {
        $this->eventManager->dispatch(
            'adminhtml_company_save_after',
            ['company' => $company, 'request' => $this->request]
        );
    }
}
