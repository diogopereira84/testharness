<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model\Quote\Item;

use Fedex\ProductBundle\Api\ConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item\Option\ComparatorInterface;

/**
 * Comparator for bundle item options, specifically for each item line.
 */
class BundleInstanceIdHash implements ComparatorInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     * @param RequestInterface $request
     */
    public function __construct(
        Json $serializer,
        private readonly RequestInterface $request,
        private readonly ConfigInterface $config,
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function compare(DataObject $option1, DataObject $option2): bool
    {
        if (!$this->config->isTigerE468338ToggleEnabled()) {
           return true;
        }

        $value1 = $option1->getValue() ?? false;
        $value2 = $option2->getValue() ?? false;

        return $value1 === $value2;
    }
}
