<?php
/**
 * @category  Fedex
 * @package   Fedex_GraphQl
 * @copyright Copyright (c) 2025 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Service;

use Fedex\GraphQl\Service\CheckLogEnabledForMutation;
use Fedex\InStoreConfigurations\Model\System\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckLogEnabledForMutationTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var CheckLogEnabledForMutation
     */
    private $checkLogEnabledForMutation;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->checkLogEnabledForMutation = new CheckLogEnabledForMutation($this->configMock);
    }

    /**
     * Test execution with empty mutation name
     */
    public function testExecuteWithEmptyMutationName(): void
    {
        $result = $this->checkLogEnabledForMutation->execute('');
        $this->assertFalse($result, 'Should return false when mutation name is empty');
    }

    /**
     * Test execution with mutation name that is in the allowed list
     */
    public function testExecuteWithAllowedMutationName(): void
    {
        $mutationName = 'allowedMutation';
        $allowedMutations = ['allowedMutation', 'anotherAllowedMutation'];

        $this->configMock->expects($this->once())
            ->method('getNewrelicGraphqlMutationsList')
            ->willReturn($allowedMutations);

        $result = $this->checkLogEnabledForMutation->execute($mutationName);
        $this->assertTrue($result, 'Should return true when mutation name is in the allowed list');
    }

    /**
     * Test execution with mutation name that is not in the allowed list
     */
    public function testExecuteWithNotAllowedMutationName(): void
    {
        $mutationName = 'notAllowedMutation';
        $allowedMutations = ['allowedMutation', 'anotherAllowedMutation'];

        $this->configMock->expects($this->once())
            ->method('getNewrelicGraphqlMutationsList')
            ->willReturn($allowedMutations);

        $result = $this->checkLogEnabledForMutation->execute($mutationName);
        $this->assertFalse($result, 'Should return false when mutation name is not in the allowed list');
    }

    /**
     * Test execution with empty allowed mutations list
     */
    public function testExecuteWithEmptyAllowedMutationsList(): void
    {
        $mutationName = 'someMutation';
        $allowedMutations = [];

        $this->configMock->expects($this->once())
            ->method('getNewrelicGraphqlMutationsList')
            ->willReturn($allowedMutations);

        $result = $this->checkLogEnabledForMutation->execute($mutationName);
        $this->assertFalse($result, 'Should return false when allowed mutations list is empty');
    }
}
