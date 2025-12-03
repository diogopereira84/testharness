<?php
namespace Fedex\Catalog\Plugin\Model\Entity\Attribute;

use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Eav\Model\Entity\Attribute\OptionManagement;

class OptionManagementPlugin
{
    /**
     * Around plugin for the add method.
     *
     * @param OptionManagement $subject
     * @param \Closure $proceed
     * @param int $entityType
     * @param string $attributeCode
     * @param AttributeOptionInterface $option
     * @return string
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @codeCoverageIgnore
     */
    public function aroundAdd(
        OptionManagement $subject,
        \Closure $proceed,
        string $entityType,
        string $attributeCode,
        AttributeOptionInterface $option,
        $isAddAttributeOptEnalable = false
    ) {
        if (!$isAddAttributeOptEnalable) {
            return $proceed($entityType, $attributeCode, $option);
        }
        
        $attribute = self::invokeMethod($subject, 'loadAttribute', [$entityType, $attributeCode]);

        $label = trim((string)$option->getLabel());
        if ($label === '') {
            throw new InputException(__('The attribute option label is empty. Enter the value and try again.'));
        }

        $optionId = self::invokeMethod($subject, 'getNewOptionId', [$option]);
        self::invokeMethod($subject, 'saveOption', [$attribute, $option, $optionId]);

        return self::invokeMethod($subject, 'retrieveOptionId', [$attribute, $option]);
    }

    /**
     * Dynamically invoke a private or protected method on an object.
     *
     * @param object $object The object instance.
     * @param string $methodName The method name.
     * @param array $params Parameters to pass to the method.
     * @return mixed The result of the method invocation.
     * @throws ReflectionException
     * @codeCoverageIgnore
     */
    public static function invokeMethod($object, string $methodName, array $params = [])
    {
        $reflectionClass = new \ReflectionClass($object);
        if (!$reflectionClass->hasMethod($methodName)) {
            throw new \InvalidArgumentException("Method $methodName does not exist.");
        }

        $method = $reflectionClass->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invoke($object, ...$params);
    }
}
