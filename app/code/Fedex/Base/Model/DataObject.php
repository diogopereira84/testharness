<?php
/**
 * @category  Fedex
 * @package   Fedex_Base
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Base\Model;

use Fedex\Base\Api\Data\DataObjectInterface;
use Fedex\Base\Exception\RecursionException;

class DataObject extends \Magento\Framework\DataObject implements DataObjectInterface
{
    /**
     * @inheritDoc
     * @throws RecursionException
     */
    public function toArrayRecursive(array $keys = [], array &$visited = []): array
    {
        if (empty($keys)) {
            $keys = array_keys($this->_data);
        }
        $objectId = spl_object_id($this);
        if (isset($visited[$objectId])) {
            throw new RecursionException(
                __(
                    '%1',
                    'Recursion found in "' . __METHOD__ . '"'
                    . ' with object ID "' . $objectId . '"'
                    . ' and keys "' . implode(', ', $keys) . '"'
                )
            );
        }
        $visited[$objectId] = true;
        $result = [];
        foreach ($keys as $key) {
            if (isset($this->_data[$key])) {
                if ($this->_data[$key] instanceof DataObjectInterface) {
                    $keys = $keys[$key] ?? [];
                    $result[$key] = $this->_data[$key]->toArrayRecursive($keys, $visited);
                    continue;
                }
                $result[$key] = $this->_data[$key];
            } else {
                $result[$key] = null;
            }
        }
        return $result;
    }
}
