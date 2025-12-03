<?php
declare(strict_types=1);

namespace Fedex\ProductBundle\Model\CartItemProcessor;

use Magento\Framework\ObjectManager\NoninterceptableInterface;

/**
 * Proxy class for @see \Fedex\ProductBundle\Model\CartItemProcessor
 */
class Proxy extends \Fedex\ProductBundle\Model\CartItemProcessor implements NoninterceptableInterface
{
    /**
     * Proxied instance
     *
     * @var \Magento\Bundle\Model\CartItemProcessor
     */
    protected $_subject = null;

    /**
     * Proxy constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $_objectManager
     * @param string $_instanceName
     * @param bool $_shared
     */
    public function __construct(
        protected \Magento\Framework\ObjectManagerInterface $_objectManager,
        protected $_instanceName = '\\Fedex\\ProductBundle\\Model\\CartItemProcessor',
        protected $_isShared = true
    ) {}

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['_subject', '_isShared', '_instanceName'];
    }

    /**
     * Retrieve ObjectManager from global scope
     */
    public function __wakeup()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     */
    public function __clone()
    {
        if ($this->_subject) {
            $this->_subject = clone $this->_getSubject();
        }
    }

    /**
     * Debug proxied instance
     */
    public function __debugInfo()
    {
        return ['i' => $this->_subject];
    }

    /**
     * Get proxied instance
     *
     * @return \Fedex\ProductBundle\Model\CartItemProcessor
     */
    protected function _getSubject()
    {
        if (!$this->_subject) {
            $this->_subject = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }
        return $this->_subject;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToBuyRequest(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        return $this->_getSubject()->convertToBuyRequest($cartItem);
    }

    /**
     * {@inheritdoc}
     */
    public function processOptions(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        return $this->_getSubject()->processOptions($cartItem);
    }
}
