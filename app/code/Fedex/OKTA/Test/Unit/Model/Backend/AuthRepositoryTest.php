<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\Backend;

use Fedex\OKTA\Model\ResourceModel\Backend\Auth;
use Fedex\OKTA\Model\Backend\AuthRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthRepositoryTest extends TestCase
{
    /**
     * @var AuthRepository
     */
    private AuthRepository $authRepository;

    /**
     * @var Auth|MockObject
     */
    private Auth $authMock;

    protected function setUp(): void
    {
        $this->authMock = $this->createMock(Auth::class);
        $this->authRepository = new AuthRepository($this->authMock);
    }

    public function testGetRelationshipId(): void
    {
        $this->authMock->expects($this->once())->method('getRelationshipId')->willReturn(123);

        $this->assertEquals(123, $this->authRepository->getRelationshipId('someUser'));
    }

    public function testGetRelationshipIdInvalid(): void
    {
        $this->authMock->expects($this->once())->method('getRelationshipId')->willReturn(null);

        $this->assertFalse($this->authRepository->getRelationshipId('someUser'));
    }

    public function testAddRelationship(): void
    {
        $this->authMock->expects($this->once())->method('addRelationship')->willReturn(true);

        $this->assertTrue($this->authRepository->addRelationship('someUser', 456));
    }

    public function testGetRelationshipData(): void
    {
        $data = ['some_data'];
        $this->authMock->expects($this->once())->method('getRelationshipData')->willReturn($data);

        $this->assertEquals($data, $this->authRepository->getRelationshipData(567));
    }
}
