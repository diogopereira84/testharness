<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model;

use Fedex\GraphQl\Api\BatchCommandInterface;
use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Config\Element\Field;

class GraphQlBatchRequestCommand extends DataObject implements BatchCommandInterface
{
    private const FIELD = 'field';
    private const CONTEXT = 'context';
    private const RESULT = 'result';
    private const REQUEST = 'requests';

    /**
     * GraphQlRequestCommand constructor.
     * @param Field $field
     * @param $context
     * @param array $requests
     */
    public function __construct(
        Field $field,
        $context,
        array $requests = null,
        array $result = null
    ) {
        parent::__construct([
            self::FIELD => $field,
            self::CONTEXT => $context,
            self::REQUEST => $requests,
            self::RESULT => $result
        ]);
    }

    /**
     * @return Field
     */
    public function getField(): Field
    {
        return $this->getData(self::FIELD);
    }

    /**
     * @return object
     */
    public function getContext(): object
    {
        return $this->getData(self::CONTEXT);
    }

    /**
     * @return array|null
     */
    public function getResult(): ?array
    {
        return $this->getData(self::RESULT);
    }

    /**
     * @return array|null
     */
    public function getRequests(): ?array
    {
        return $this->getData(self::REQUEST);
    }
}
