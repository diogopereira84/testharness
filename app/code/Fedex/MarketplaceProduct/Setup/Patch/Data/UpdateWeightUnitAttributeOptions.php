<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class UpdateWeightUnitAttributeOptions implements DataPatchInterface
{
    private const WEIGHT_UNIT_ATTRIBUTE = 'weight_unit';
    private const WEIGHT_UNIT_OPTIONS_MAP = [
        'ounce' => 'oz.',
        'pound' => 'lbs.',
    ];

    /**
     * @param LoggerInterface $logger
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        private LoggerInterface $logger,
        private ProductAttributeRepositoryInterface $productAttributeRepository
    )
    {
    }

    /**
     * Apply patch
     *
     * @return void
     */
    public function apply(): void
    {
        try {
            $options = [];
            $attribute = $this->productAttributeRepository->get(self::WEIGHT_UNIT_ATTRIBUTE);
            foreach ($attribute->getOptions() as $option) {
                if (!empty(trim($option->getLabel())) && isset(self::WEIGHT_UNIT_OPTIONS_MAP[$option->getLabel()])) {
                    $option->setLabel(self::WEIGHT_UNIT_OPTIONS_MAP[$option->getLabel()]);
                    $options[] = $option;
                }
            }
            if (count($options)) {
                $attribute->setOptions($options);
                $this->productAttributeRepository->save($attribute);
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            CreateWeightUnitAttribute::class
        ];
    }
}
