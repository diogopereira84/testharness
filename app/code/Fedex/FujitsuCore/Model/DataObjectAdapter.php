<?php
/**
 * @category     Fedex
 * @package      Fedex_FujitsuCore
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\FujitsuCore\Model;

use function lcfirst;
use Magento\Framework\DataObject as MagentoDataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use function substr;

class DataObjectAdapter extends MagentoDataObject
{
    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Set/Get attribute wrapper
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws LocalizedException
     */
    public function __call($method, $args)
    {
        switch (substr((string)$method, 0, 3)) {
            case 'get':
                $key = lcfirst(substr($method, 3));
                $index = isset($args[0]) ? $args[0] : null;
                return $this->getData($key, $index);
            case 'set':
                $key = lcfirst(substr($method, 3));
                $value = isset($args[0]) ? $args[0] : null;
                return $this->setData($key, $value);
            case 'uns':
                $key = lcfirst(substr($method, 3));
                return $this->unsetData($key);
            case 'has':
                $key = lcfirst(substr($method, 3));
                return isset($this->_data[$key]);
        }

        throw new LocalizedException(
            new Phrase('Invalid method %1::%2', [get_class($this), $method])
        );
    }

    /**
     * Convert all object to array
     *
     * @param mixed $data
     * @return array
     */
    public function convertAllToArray(mixed $data = null): array
    {
        $array = [];
        $curData = $data ? $data->getData() : $this->getData();
        foreach ($curData as $key => $data) {
            if (is_object($data)) {
                $array[$key] = $this->convertAllToArray($data);
            } else {
                $array[$key] = $data;
            }
        }
        return $array;
    }
}
