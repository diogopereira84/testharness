<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Plugin\Model\Email;

use Fedex\Company\Plugin\Model\Email\Sender;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Model\Email\Sender as CompanyEmailSender;
use Magento\Customer\Api\Data\CustomerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit test for Fedex\Company\Plugin\Model\Email\Sender
 */
class SenderTest extends TestCase
{
    /**
     * @var Sender
     */
    private $plugin;

    /**
     * @var MockObject|LoggerInterface
     */
    private $loggerMock;

    /**
     * @var MockObject|ToggleConfig
     */
    private $toggleConfigMock;

    /**
     * @var MockObject|CompanyEmailSender
     */
    private $companyEmailSenderMock;

    /**
     * @var MockObject|CustomerInterface
     */
    private $customerMock;

    /**
     * @var callable|MockObject
     */
    private $proceedMock;

    /**
     * Setup test dependencies
     *
     * @return void
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->companyEmailSenderMock = $this->getMockBuilder(CompanyEmailSender::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->customerMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        // Create a mock for the proceed callable
        $this->proceedMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['__invoke'])
            ->getMock();

        // Use reflection to avoid type checking issues in tests
        $reflectionClass = new \ReflectionClass(Sender::class);
        $this->plugin = $reflectionClass->newInstanceWithoutConstructor();
        
        // Set private properties using reflection
        $loggerProperty = $reflectionClass->getProperty('logger');
        $loggerProperty->setAccessible(true);
        $loggerProperty->setValue($this->plugin, $this->loggerMock);
        
        $toggleProperty = $reflectionClass->getProperty('toggleConfig');
        $toggleProperty->setAccessible(true);
        $toggleProperty->setValue($this->plugin, $this->toggleConfigMock);
    }

    /**
     * Test that email is blocked when toggle is enabled
     *
     * @return void
     */
    public function testEmailBlockedWhenToggleEnabled(): void
    {
        $customerId = 123;
        $companyId = 456;

        // Setup mocks
        $this->customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tech_titans_D_230786')
            ->willReturn(true);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'FedEx Company Plugin: sendAssignSuperUserNotificationEmail blocked for customer ID: ' . 
                $customerId . ', Company ID: ' . $companyId . ' due to toggle being enabled'
            );

        // Proceed should NOT be called when toggle is enabled
        $this->proceedMock->expects($this->never())
            ->method('__invoke');

        // Execute the method
        $result = $this->plugin->aroundSendAssignSuperUserNotificationEmail(
            $this->companyEmailSenderMock,
            $this->proceedMock,
            $this->customerMock,
            $companyId
        );

        // Assertions
        $this->assertSame($this->companyEmailSenderMock, $result);
        $this->assertInstanceOf(CompanyEmailSender::class, $result);
        $this->assertNotNull($result);
        $this->assertTrue(is_object($result));
    }

    /**
     * Test that email proceeds when toggle is disabled
     *
     * @return void
     */
    public function testEmailProceedsWhenToggleDisabled(): void
    {
        $customerId = 789;
        $companyId = 101;

        // Setup mocks
        $this->customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tech_titans_D_230786')
            ->willReturn(false);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'FedEx Company Plugin: sendAssignSuperUserNotificationEmail proceeding for customer ID: ' . 
                $customerId . ', Company ID: ' . $companyId . ' as toggle is disabled'
            );

        // Proceed should be called when toggle is disabled
        $this->proceedMock->expects($this->once())
            ->method('__invoke')
            ->with($this->customerMock, $companyId)
            ->willReturn($this->companyEmailSenderMock);

        // Execute the method
        $result = $this->plugin->aroundSendAssignSuperUserNotificationEmail(
            $this->companyEmailSenderMock,
            $this->proceedMock,
            $this->customerMock,
            $companyId
        );

        // Assertions
        $this->assertSame($this->companyEmailSenderMock, $result);
        $this->assertInstanceOf(CompanyEmailSender::class, $result);
        $this->assertNotNull($result);
        $this->assertTrue(is_object($result));
    }

    /**
     * Test that correct parameters are passed to toggle config
     *
     * @return void
     */
    public function testToggleConfigParameterValidation(): void
    {
        $customerId = 555;
        $companyId = 777;

        $this->customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        // Verify exact toggle key is used
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with($this->equalTo('tech_titans_D_230786'))
            ->willReturn(false);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('FedEx Company Plugin: sendAssignSuperUserNotificationEmail'));

        $this->proceedMock->expects($this->once())
            ->method('__invoke')
            ->willReturn($this->companyEmailSenderMock);

        $result = $this->plugin->aroundSendAssignSuperUserNotificationEmail(
            $this->companyEmailSenderMock,
            $this->proceedMock,
            $this->customerMock,
            $companyId
        );

        $this->assertSame($this->companyEmailSenderMock, $result);
    }

    /**
     * Test logger message content and format
     *
     * @return void
     */
    public function testLoggerMessageFormat(): void
    {
        $customerId = 999;
        $companyId = 888;
        $expectedBlockedMessage = 'FedEx Company Plugin: sendAssignSuperUserNotificationEmail blocked for customer ID: ' . 
            $customerId . ', Company ID: ' . $companyId . ' due to toggle being enabled';

        $this->customerMock->expects($this->once())
            ->method('getId')
            ->willReturn($customerId);

        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        // Test exact log message format
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->equalTo($expectedBlockedMessage));

        $result = $this->plugin->aroundSendAssignSuperUserNotificationEmail(
            $this->companyEmailSenderMock,
            $this->proceedMock,
            $this->customerMock,
            $companyId
        );

        $this->assertSame($this->companyEmailSenderMock, $result);
        $this->assertIsObject($result);
    }
}