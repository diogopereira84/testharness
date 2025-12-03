<?php
/**
 * @category  Fedex
 * @package   Fedex_Canva
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\Canva\Plugin\Model\Page;

use Exception;
use Fedex\Canva\Model\Builder;
use Magento\Cms\Model\Page\DataProvider;
use Magento\Framework\Serialize\SerializerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class DataProviderPlugin
{
    private const CANVA_SIZES = 'canva_sizes';
    private const DEFAULT = 'default';

    public function __construct(
        readonly protected Builder             $builder,
        readonly protected SerializerInterface $serializer,
        readonly protected ToggleConfig        $toggleConfig
    )
    {
    }

    /**
     * @throws Exception
     */
    public function afterGetData(DataProvider $subject, $loadedData): array
    {
        if($this->toggleConfig->getToggleConfigValue('tigers_b2185176_remove_adobe_commerce_overrides')){
            foreach ($loadedData as $key => $item) {
                $loadedData[$key][self::CANVA_SIZES] = $item[self::CANVA_SIZES] ?? [];
                $loadedData[$key][self::DEFAULT] = $item[self::DEFAULT] ?? 'option_0';
                if (is_string($loadedData[$key][self::CANVA_SIZES])) {
                    $collection = $this->builder->build($this->serializer->unserialize($item[self::CANVA_SIZES]));
                    $loadedData[$key][self::CANVA_SIZES] = $collection->toArray();
                    $loadedData[$key][self::DEFAULT] = $collection->getDefaultOptionId();
                }
            }
        }
        return $loadedData;
    }
}
