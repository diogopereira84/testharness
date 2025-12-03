<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Api;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;

interface GraphQlBatchValidationInterface
{
    /**
     * @param GraphQlBatchRequestCommand $requestCommand
     */
    public function validate(GraphQlBatchRequestCommand $requestCommand): void;
}
