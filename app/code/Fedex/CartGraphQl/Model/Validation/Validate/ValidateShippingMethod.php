<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Validation\Validate;

use Fedex\GraphQl\Api\GraphQlValidationInterface;
use Fedex\GraphQl\Model\GraphQlRequestCommand;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Psr\Log\LoggerInterface;

class ValidateShippingMethod implements GraphQlValidationInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected LoggerInterface $logger
    )
    {
    }

    public function validate(GraphQlRequestCommand $requestCommand): void
    {
        $args = $requestCommand->getArgs();
        if (empty($args['input']['pickup_data']) && empty($args['input']['shipping_data'])) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' Required parameter pickup_data or shipping_data is missing.');
            throw new GraphQlInputException(
                __('Required parameter pickup_data or shipping_data is missing.')
            );
        } elseif (!empty($args['input']['pickup_data']) && !empty($args['input']['shipping_data'])) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' You should provide just pickup_data or shipping_data parameter.');
            throw new GraphQlInputException(
                __('You should provide just pickup_data or shipping_data parameter.')
            );
        }
    }
}
