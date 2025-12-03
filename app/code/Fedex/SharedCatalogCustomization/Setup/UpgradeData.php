<?php
namespace Fedex\SharedCatalogCustomization\Setup;

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\SharedCatalog\Api\Data\SharedCatalogInterface;
use Magento\SharedCatalog\Model\Repository;
use Magento\SharedCatalog\Model\SharedCatalogFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\User\Api\Data\UserInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;

class UpgradeData implements UpgradeDataInterface
{
    public $defaultUserId;
    public const SHARED_CATALOG_NAME = 'OnDemand Commercial Products';

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(
        private SharedCatalogFactory            $catalogFactory,
        private Repository                      $sharedCatalogRepository,
        private GroupInterfaceFactory           $groupFactory,
        private GroupRepositoryInterface        $groupRepository,
        private TaxClassRepositoryInterface     $taxClassRepository,
        private SearchCriteriaBuilder           $searchCriteriaBuilder,
        private UserCollectionFactory           $userCollectionFactory,
        private ModuleDataSetupInterface        $moduleDataSetup
    )
    {
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @codeCoverageIgnore
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')
            && is_null($this->checkIfSharedCatalogExist())) {
            /**
             * @var $sharedCatalog SharedCatalogInterface
             */
            $sharedCatalog = $this->catalogFactory->create();
            $customerGroup = $this->createCustomerGroup();
            $sharedCatalog->setName(self::SHARED_CATALOG_NAME)
                ->setDescription('This Shared Catalog with default print products to be associated with a site
                when its first created.')
                ->setCreatedBy($this->getDefaultUserId())
                ->setType(SharedCatalogInterface::TYPE_CUSTOM)
                ->setCustomerGroupId($customerGroup->getId())
                ->setTaxClassId($this->getRetailCustomerTaxClassId());

            $this->sharedCatalogRepository->save($sharedCatalog);
        }
        $installer->endSetup();
    }

    /**
     * Get id of retail customer tax class.
     *
     * @return int|null
     */
    private function getRetailCustomerTaxClassId()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('class_type', \Magento\Tax\Model\ClassModel::TAX_CLASS_TYPE_CUSTOMER)
            ->create();
        $customerTaxClasses = $this->taxClassRepository->getList($searchCriteria)->getItems();
        $customerTaxClass = array_shift($customerTaxClasses);

        return ($customerTaxClass && $customerTaxClass->getClassId()) ? $customerTaxClass->getClassId() : null;
    }

    /**
     * @return int|null
     * @throws LocalizedException
     */
    private function checkIfSharedCatalogExist()
    {

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('name', self::SHARED_CATALOG_NAME)
            ->create();
        $sharedCatalogList = $this->sharedCatalogRepository->getList($searchCriteria)->getItems();
        /** @var SharedCatalogInterface $sharedCatalog */
        $sharedCatalog = array_shift($sharedCatalogList);

        return ($sharedCatalog && $sharedCatalog->getId()) ? $sharedCatalog->getId() : null;
    }

    /**
     * @return GroupInterface
     * @throws CouldNotSaveException
     */
    private function createCustomerGroup()
    {
        /** @var GroupInterface $customerGroup */
        $customerGroup = $this->groupFactory->create();
        $customerGroup->setCode('OnDemand Commercial Products Shared Catalog');
        $customerGroup->setTaxClassId($this->getRetailCustomerTaxClassId());
        try {
            $customerGroup = $this->groupRepository->save($customerGroup);
        } catch (InvalidTransitionException $e) {
            throw new CouldNotSaveException(
                __('A customer group with this name already exists. Enter a different name to create a shared catalog.')
            );
        } catch (LocalizedException $e) {
            throw new CouldNotSaveException(__('Could not save customer group.'));
        }

        return $customerGroup;
    }

    /**
     * Get default user id.
     *
     * @return int
     */
    private function getDefaultUserId()
    {
        /** @var \Magento\User\Model\ResourceModel\User\Collection $userCollection */
        $userCollection = $this->userCollectionFactory->create();
        /** @var UserInterface $user */
        $user = $userCollection->setPageSize(1)->getFirstItem();

        return $user->getId() ?: $this->defaultUserId;
    }
}
