<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai solanki
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Plugin;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema;
use Magento\Framework\GraphQl\Query\QueryProcessor;

class ValidateCartIdExceptionChange
{
    protected const MESSAGE = 'message';

    /**
     * @param QueryProcessor $subject
     * @param array $result
     * @param Schema $schema
     * @param string $source
     * @param ContextInterface|null $contextValue
     * @param array|null $variableValues
     * @param string|null $operationName
     * @return array
     */
    public function afterProcess(
        QueryProcessor   $subject,
        array            $result,
        Schema           $schema,
        string           $source,
        ContextInterface $contextValue = null,
        array            $variableValues = null,
        string           $operationName = null
    ): array {
        if (isset($result['errors'])) {
            $blankVariables = $this->getBlankVariable($variableValues);
            foreach ($blankVariables as $newValue) {
                $result['errors'] = $this->updateMessage($result['errors'], $newValue);
            }
        }
        return $result;
    }

    /**
     * @param array $variableValues
     * @return array
     */
    private function getBlankVariable(array $variableValues): array
    {
        $blankVariables = [];
        foreach ($variableValues as $key => $variableValue) {
            if (is_array($variableValue)) {
                $blankVariables = array_merge($blankVariables, $this->getBlankVariable($variableValue));
            } elseif ($variableValue === null) {
                $blankVariables[] = $key;
            }
        }
        return $blankVariables;
    }

    /**
     * @param array|string $value
     * @param string $newValue
     * @param mixed $key
     * @return array|string
     */
    private function updateMessageKey(mixed $value, string $newValue, mixed $key): mixed
    {
        if (is_array($value)) {
            return $this->updateMessage($value, $newValue);
        } elseif ($this->isKeyAvailable($key, $value, $newValue)) {
            return 'Missing Parameter "' . $newValue . '". ' . $value;
        }
        return $value;
    }

    /**
     * @param array $array |null $array
     * @param string $newValue
     * @return array|null
     */
    private function updateMessage(array $array, string $newValue): ?array
    {
        foreach ($array as $key => $value) {
            $array[$key] = $this->updateMessageKey($value, $newValue, $key);
        }
        return $array;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param string $newValue
     * @return bool
     */
    private function isKeyAvailable(mixed $key, mixed $value, string $newValue): bool
    {
        return $key === self::MESSAGE && str_contains($value, $newValue);
    }
}
