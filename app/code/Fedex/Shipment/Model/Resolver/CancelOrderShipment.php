<?php

declare(strict_types=1);

namespace Fedex\Shipment\Model\Resolver;

use Fedex\Shipment\Model\NewOrderUpdate;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Mirakl orders cancel resolver
 */
class CancelOrderShipment implements ResolverInterface
{
    /**
     * @param NewOrderUpdate $newOrderUpdate
     */
    public function __construct(
        private NewOrderUpdate $newOrderUpdate
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field       $field,
                    $context,
        ResolveInfo $info,
        ?array      $value = null,
        ?array      $args = null
    ): array
    {
        if (empty($args['input']["fxoWorkOrderNumber"])) {
            throw new GraphQlInputException(
                __('Required parameter "%s" is missing', 'fxoWorkOrderNumber')
            );
        }

        if (empty($args['input']["shipmentItems"])) {
            throw new GraphQlInputException(
                __('Required parameter "%s" is missing', 'shipmentItems')
            );
        }

        $request = [
            'fxoWorkOrderNumber' => $args['input']["fxoWorkOrderNumber"],
            'customerOrderNumber' => $args['input']["fxoWorkOrderNumber"],
            'orderCreatedBySystem' => "MAGENTO",
            'transactionId' => "",
            'shipmentItems' => $args['input']["shipmentItems"]
        ];

        $result = $this->newOrderUpdate->updateOrderStatus($args['input']["fxoWorkOrderNumber"], json_encode($request));

        return [
            'result' => json_encode($result)
        ];
    }
}
