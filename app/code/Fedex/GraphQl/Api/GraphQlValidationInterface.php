<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Api;

use Fedex\GraphQl\Model\GraphQlRequestCommand;

interface GraphQlValidationInterface
{
    /**
     * @param GraphQlRequestCommand $requestCommand
     */
    public function validate(GraphQlRequestCommand $requestCommand): void;
}
