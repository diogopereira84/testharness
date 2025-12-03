<?php

declare(strict_types=1);

namespace Fedex\ProductBundle\Test\Unit\Model;

use Fedex\ProductBundle\Model\CartItemProcessor\Proxy;
use Magento\Framework\ObjectManagerInterface;
use Fedex\ProductBundle\Model\CartItemProcessor;
use Magento\Quote\Api\Data\CartItemInterface;
use PHPUnit\Framework\TestCase;

class CartItemProcessorProxyTest extends TestCase
{
    private $objectManager;
    private $subject;
    private $cartItem;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->subject = $this->createMock(CartItemProcessor::class);
        $this->cartItem = $this->createMock(CartItemInterface::class);
    }

    public function testSleepReturnsExpectedProperties()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $result = $proxy->__sleep();
        $this->assertEquals(['_subject', '_isShared', '_instanceName'], $result);
    }

    public function testWakeupSetsObjectManager()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $proxy->__wakeup();
        $reflection = new \ReflectionProperty($proxy, '_objectManager');
        $reflection->setAccessible(true);
        $objectManagerValue = $reflection->getValue($proxy);
        $this->assertInstanceOf(ObjectManagerInterface::class, $objectManagerValue);
    }

    public function testCloneWithSubjectClonesSubject()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $reflectionSubject = new \ReflectionProperty($proxy, '_subject');
        $reflectionSubject->setAccessible(true);
        $reflectionSubject->setValue($proxy, $this->subject);
        $reflectionIsShared = new \ReflectionProperty($proxy, '_isShared');
        $reflectionIsShared->setAccessible(true);
        $reflectionIsShared->setValue($proxy, true);
        $this->objectManager->method('get')->willReturn($this->subject);
        $cloned = clone $proxy;
        $reflectionCloned = new \ReflectionProperty($cloned, '_subject');
        $reflectionCloned->setAccessible(true);
        $clonedSubject = $reflectionCloned->getValue($cloned);
        $this->assertInstanceOf(Proxy::class, $cloned);
        $this->assertInstanceOf(CartItemProcessor::class, $clonedSubject);
    }

    public function testCloneWithoutSubjectDoesNothing()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $reflection = new \ReflectionProperty($proxy, '_subject');
        $reflection->setAccessible(true);
        $reflection->setValue($proxy, null);
        $cloned = clone $proxy;
        $reflectionCloned = new \ReflectionProperty($cloned, '_subject');
        $reflectionCloned->setAccessible(true);
        $clonedSubject = $reflectionCloned->getValue($cloned);
        $this->assertNull($clonedSubject);
    }

    public function testDebugInfoReturnsSubject()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $reflection = new \ReflectionProperty($proxy, '_subject');
        $reflection->setAccessible(true);
        $reflection->setValue($proxy, $this->subject);
        $result = $proxy->__debugInfo();
        $this->assertArrayHasKey('i', $result);
        $this->assertSame($this->subject, $result['i']);
    }

    public function testGetSubjectShared()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $reflectionSubject = new \ReflectionProperty($proxy, '_subject');
        $reflectionSubject->setAccessible(true);
        $reflectionSubject->setValue($proxy, null);
        $reflectionIsShared = new \ReflectionProperty($proxy, '_isShared');
        $reflectionIsShared->setAccessible(true);
        $reflectionIsShared->setValue($proxy, true);
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with('InstanceName')
            ->willReturn($this->subject);
        $result = $this->invokeGetSubject($proxy);
        $this->assertSame($this->subject, $result);
    }

    public function testGetSubjectNotShared()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', false);
        $reflectionSubject = new \ReflectionProperty($proxy, '_subject');
        $reflectionSubject->setAccessible(true);
        $reflectionSubject->setValue($proxy, null);
        $reflectionIsShared = new \ReflectionProperty($proxy, '_isShared');
        $reflectionIsShared->setAccessible(true);
        $reflectionIsShared->setValue($proxy, false);
        $this->objectManager->expects($this->once())
            ->method('create')
            ->with('InstanceName')
            ->willReturn($this->subject);
        $result = $this->invokeGetSubject($proxy);
        $this->assertSame($this->subject, $result);
    }

    public function testConvertToBuyRequestDelegatesToSubject()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $reflection = new \ReflectionProperty($proxy, '_subject');
        $reflection->setAccessible(true);
        $reflection->setValue($proxy, $this->subject);
        $this->subject->expects($this->once())
            ->method('convertToBuyRequest')
            ->with($this->cartItem)
            ->willReturn('result');
        $result = $proxy->convertToBuyRequest($this->cartItem);
        $this->assertSame('result', $result);
    }

    public function testProcessOptionsDelegatesToSubject()
    {
        $proxy = new Proxy($this->objectManager, 'InstanceName', true);
        $reflection = new \ReflectionProperty($proxy, '_subject');
        $reflection->setAccessible(true);
        $reflection->setValue($proxy, $this->subject);
        $this->subject->expects($this->once())
            ->method('processOptions')
            ->with($this->cartItem)
            ->willReturn('options');
        $result = $proxy->processOptions($this->cartItem);
        $this->assertSame('options', $result);
    }

    private function invokeGetSubject($proxy)
    {
        $reflection = new \ReflectionClass($proxy);
        $method = $reflection->getMethod('_getSubject');
        $method->setAccessible(true);
        return $method->invoke($proxy);
    }
}
