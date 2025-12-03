<?php
declare(strict_types=1);

namespace Fedex\SaaSCommon\Model;

use Fedex\SaaSCommon\Api\CustomerGroupAttributeHandlerInterface;
use Fedex\SaaSCommon\Api\CustomerGroupDiffServiceInterface;
use Fedex\SaaSCommon\Api\Data\AllowedCustomerGroupsRequestInterfaceFactory;
use Fedex\SaaSCommon\Model\Entity\Attribute\Source\CustomerGroupsOptions;
use Fedex\SaaSCommon\Model\Queue\Publisher;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Model Class CustomerGroupAttributeHandler
 */
class CustomerGroupAttributeHandler implements CustomerGroupAttributeHandlerInterface
{
    /**
     * Attribute code for allowed customer groups.
     */
    public const ATTRIBUTE_CODE = 'allowed_customer_groups';

    /**
     * Constructor for CustomerGroupAttributeHandler
     * .
     * @param LoggerInterface $logger
     * @param AttributeRepositoryInterface $attributeRepository
     * @param AttributeOptionInterfaceFactory $attributeOptionFactory
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param CustomerGroupsOptions $customerGroupsOptions
     * @param CustomerGroupDiffServiceInterface $customerGroupDiffServiceInterface
     * @param Publisher $publisher
     * @param AllowedCustomerGroupsRequestInterfaceFactory $allowedCustomerGroupsRequestFactory
     */
    public function __construct(
        private LoggerInterface $logger,
        private AttributeRepositoryInterface $attributeRepository,
        private AttributeOptionInterfaceFactory $attributeOptionFactory,
        private AttributeOptionManagementInterface $attributeOptionManagement,
        private CustomerGroupsOptions $customerGroupsOptions,
        private CustomerGroupDiffServiceInterface $customerGroupDiffServiceInterface,
        private Publisher $publisher,
        private AllowedCustomerGroupsRequestInterfaceFactory $allowedCustomerGroupsRequestFactory
    ) {}

    /**
     * Retrieve the attribute ID based on the entity type and attribute code.
     *
     * @param string $entityType
     * @param string $attributeCode
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttributeIdByCode(string $entityType, string $attributeCode): ?int
    {
        $attribute = $this->attributeRepository->get($entityType, $attributeCode);
        return (int)$attribute->getAttributeId();
    }

    /**
     * Retrieve an array with all customer groups.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllCustomerGroups(): array
    {
        return $this->customerGroupsOptions->getAllOptions();
    }

    /**
     * Retrieve an array with all customer groups values.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getAllCustomerGroupsValues(): array
    {
        return $this->customerGroupsOptions->getAllOptionsValues();
    }

    /**
     * Adds new attribute options for allowed customer groups.
     *
     * @param array|null $customerGroupNewOptions
     * @return void
     * @throws InputException
     */
    public function addAttributeOption(array $customerGroupNewOptions = null): void
    {
        try {
            if ($customerGroupNewOptions === null) {
                $customerGroupNewOptions = $this->customerGroupDiffServiceInterface->findMissingCustomerGroupOptions(
                    $this->getAllCustomerGroupsValues()
                );
            }

            $attributeId = $this->getAttributeIdByCode(Product::ENTITY, static::ATTRIBUTE_CODE);
            $attributeOptionsLength = $this->customerGroupDiffServiceInterface->allowedCustomerGroupsAttributeOptionLength();

            foreach ($customerGroupNewOptions as $option) {
                $attributeOption = $this->attributeOptionFactory->create();
                $attributeOption->setAttributeId($attributeId)
                    ->setLabel((string) $option)
                    ->setSortOrder($attributeOptionsLength++);

                $this->attributeOptionManagement->add(
                    Product::ENTITY,
                    static::ATTRIBUTE_CODE,
                    $attributeOption
                );
            }
        } catch (CouldNotSaveException | LocalizedException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .' Error creating attribute options:' . $e->getMessage());
            throw new InputException(__(__METHOD__ . ':' . __LINE__ . ' Unable to add attribute options.'));
        }
    }

    /**
     * Updates all attribute options for allowed customer groups.
     *
     * @return void
     * @throws InputException
     */
    public function updateAllAttributeOptions(): void
    {
        try {
            $customerGroupNewOptions = $this->customerGroupDiffServiceInterface->findMissingCustomerGroupOptions(
                $this->getAllCustomerGroupsValues()
            );

            $attributeId = $this->getAttributeIdByCode(Product::ENTITY, static::ATTRIBUTE_CODE);
            $attributeOptionsLength = $this->customerGroupDiffServiceInterface->allowedCustomerGroupsAttributeOptionLength();

            foreach ($customerGroupNewOptions as $option) {
                $attributeOption = $this->attributeOptionFactory->create();
                $attributeOption->setAttributeId($attributeId)
                    ->setLabel((string) $option)
                    ->setSortOrder($attributeOptionsLength++);

                try {
                    $this->attributeOptionManagement->add(
                        Product::ENTITY,
                        static::ATTRIBUTE_CODE,
                        $attributeOption
                    );
                } catch (InputException $e) {
                    $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Error adding attribute option: ' . $e->getMessage());
                }
            }
        } catch (CouldNotSaveException | LocalizedException $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .' Error creating attribute options:' . $e->getMessage());
            throw new InputException(__(__METHOD__ . ':' . __LINE__ . ' Unable to add attribute options.'));
        }
    }

    /**
     * Push an entity to the queue for processing.
     *
     * @param int $entityId
     * @param string $entityType
     * @return void
     */
    public function pushEntityToQueue(int $entityId, string $entityType): void
    {
        try {
            $request = $this->allowedCustomerGroupsRequestFactory->create();
            $request->setEntityId($entityId);
            $request->setEntityType($entityType);
            $this->publisher->publish($request);
        } catch (\InvalidArgumentException $e) {
            $exceptionMessage = __METHOD__ . ':' . __LINE__ . ' Error pushing entity to queue: ' . $e->getMessage();
            $this->logger->critical($exceptionMessage);
            throw new \InvalidArgumentException($exceptionMessage, 0, $e);
        }
    }
}

