<?php
/**
 * @category  Fedex
 * @package   Fedex_WebAnalytics
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\WebAnalytics\Model\Source;


use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Data\OptionSourceInterface;
use Fedex\WebAnalytics\Api\Data\GDLConfigInterface;

class PageType implements OptionSourceInterface
{
    public function __construct(
        private readonly GDLConfigInterface $config,
        private readonly JsonValidator $jsonValidator,
        private readonly Json $json,
    ) {
    }
    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        $pageTypeArray = [];
        $pageTypes = $this->config->getPageTypes();

        if ($this->jsonValidator->isValid($pageTypes)) {
            $pageTypes = $this->json->unserialize($pageTypes);
            foreach ($pageTypes as $pageType) {
                $pageTypeArray[] = [
                    'value' => $pageType['value'],
                    'label' => __($pageType['label'])
                ];
            }
        }

        return $pageTypeArray;
    }
}
