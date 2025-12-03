<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\CatalogGraphQl\Model\Resolver\Products;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class AfterProductsPlugin
{
    /**
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     */
    public function __construct(
        private readonly LoggerHelper $loggerHelper,
        private readonly NewRelicHeaders $newRelicHeaders
    ) {}

    /**
     * @param Products $subject
     * @param $result
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return mixed
     */
    public function afterResolve(
        Products $subject,
        $result,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $mutationName = $field->getName() ?? '';
        $headerArray = $this->newRelicHeaders->getHeadersForMutation($mutationName);
        if ($headerArray) {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
        }
        return $result;
    }
}
