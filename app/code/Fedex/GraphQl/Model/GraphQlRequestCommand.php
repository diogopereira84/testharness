<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Model;

use Fedex\GraphQl\Api\CommandInterface;
use Magento\Framework\DataObject;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class GraphQlRequestCommand extends DataObject implements CommandInterface
{
    private const FIELD = 'field';
    private const CONTEXT = 'context';
    private const INFO = 'info';
    private const VALUE = 'value';
    private const ARGS = 'args';
    private const RESULT = 'result';

    /**
     * GraphQlRequestCommand constructor.
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     */
    public function __construct(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null,
        array $result = null
    ) {
        parent::__construct([
            self::FIELD => $field,
            self::CONTEXT => $context,
            self::INFO => $info,
            self::VALUE => $value,
            self::ARGS => $args,
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
     * @return ResolveInfo
     */
    public function getInfo(): ResolveInfo
    {
        return $this->getData(self::INFO);
    }

    /**
     * @return array|null
     */
    public function getValue(): ?array
    {
        return $this->getData(self::VALUE);
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->getData(self::ARGS);
    }

    /**
     * @return array|null
     */
    public function getResult(): ?array
    {
        return $this->getData(self::RESULT);
    }
}
