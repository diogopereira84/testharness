<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\ProductCustomAtrribute\Setup\Patch\Data;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;
use Zend_Validate_Exception;

class InsertProductAdditionalFields implements DataPatchInterface
{
    /** @var string */
    const ATTRIBUTE_CODE_HAS_CANVA_DESIGN = 'has_canva_design';

    /** @var string */
    const ATTRIBUTE_GROUP = 'General';

    /** @var string */
    const BACKEND_TYPE_INT = 'int';

    /** @var string */
    const BACKEND_TYPE_VARCHAR = 'varchar';

    /** @var string  */
    const INPUT_TYPE_SELECT = 'select';

    /** @var string  */
    const INPUT_TYPE_TEXT = 'text';

    /** @var string  */
    const INPUT_TYPE_BOOLEAN = 'boolean';

    /** @var int */
    const DEFAULT_DISABLE = 0;

    /** @var int  */
    const DEFAULT_SORT_ORDER = 90;

    /** @var int  */
    const DEFAULT_POSITION = 90;

    /**
     * @var EavSetup
     */
    private $eavSetup;

    /**
     * InsertProductAdditionalFields constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @param LoggerInterface $logger
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        private EavSetupFactory $eavSetupFactory,
        private LoggerInterface $logger,
        private ModuleDataSetupInterface $moduleDataSetup,
        private AttributeRepositoryInterface $attributeRepository
    )
    {
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return bool|InsertProductAdditionalFields
     */
    public function apply()
    {
        /** @var array $definition */
        foreach ($this->getDefinitions() as $code => $definition) {
            try {
                $this->createAttribute($code, $definition);
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $code
     * @param array $definition
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Zend_Validate_Exception
     */
    private function createAttribute(string $code, array $definition)
    {
        $this->eavSetup()->addAttribute(
            Product::ENTITY,
            $code,
            $definition
        );

        $attribute = $this->attributeRepository->get(
            Product::ENTITY,
            $code
        );

        if (!$attribute->getAttributeId()) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' The product attribute was not created during the patch execution.');
            throw new \LogicException("The product attribute was not created during the patch execution.");
        }

        $attribute->save($attribute);

        return true;
    }

    /**
     * Get attribute definitions.
     *
     * @return array
     */
    private function getDefinitions() : array
    {
        return [
            self::ATTRIBUTE_CODE_HAS_CANVA_DESIGN => [
                'group' => self::ATTRIBUTE_GROUP,
                'type' => self::BACKEND_TYPE_INT,
                'label' => 'Has Canva Design',
                'input' => self::INPUT_TYPE_BOOLEAN,
                'frontend' => '',
                'backend' => '',
                'required' => false,
                'sort_order' => self::DEFAULT_POSITION,
                'default' => null,
                'source' => Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'is_used_in_grid' => false,
                'apply_to' => null,
                'used_in_product_listing' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
                'visible' => true,
                'is_html_allowed_on_front' => false,
                'visible_on_front' => true
            ]
        ];
    }

    /**
     * @return EavSetup
     */
    private function eavSetup()
    {
        if (!$this->eavSetup) {
            /** @var EavSetup $eavSetup */
            $this->eavSetup = $this->eavSetupFactory->create(
                ['setup' => $this->moduleDataSetup]
            );
        }
        return $this->eavSetup;
    }
}
