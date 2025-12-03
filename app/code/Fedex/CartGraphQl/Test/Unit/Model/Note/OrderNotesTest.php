<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Note;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\CartGraphQl\Model\Note\OrderNotes;
use Magento\Framework\Exception\NoSuchEntityException;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;

class OrderNotesTest extends TestCase
{
    /**
     * @var CartIntegrationNoteRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartIntegrationNoteRepositoryMock;

    /**
     * @var OrderNotes
     */
    private $orderNotes;

    protected function setUp(): void
    {
        $this->cartIntegrationNoteRepositoryMock = $this->createMock(CartIntegrationNoteRepositoryInterface::class);
        $this->orderNotes = new OrderNotes(
            $this->cartIntegrationNoteRepositoryMock
        );
    }

    public function testGetCurrentNotesWithNotesSet(): void
    {
        $notes = json_encode(['text' => 'Text Example', 'audit' => []]);
        $cartId = 1;

        $result = $this->orderNotes->getCurrentNotes($notes, $cartId);
        $this->assertEquals($notes, $result);
    }

    public function testGetCurrentNotesWithNoNotesAndIsNotesNullFalse(): void
    {
        $notes = null;
        $cartId = 1;
        $isNotesNull = false;

        $cartIntegrationNoteMock = $this->createMock(CartIntegrationNoteInterface::class);
        $cartIntegrationNoteMock->method('getId')->willReturn(1);
        $cartIntegrationNoteMock->method('getNote')->willReturn('Sample Note');

        $this->cartIntegrationNoteRepositoryMock->expects($this->once())
            ->method('getByParentId')
            ->with($cartId)
            ->willReturn($cartIntegrationNoteMock);

        $result = $this->orderNotes->getCurrentNotes($notes, $cartId, $isNotesNull);
        $this->assertEquals('Sample Note', $result);
    }

    public function testGetCurrentNotesWithNoNotesAndIsNotesNullTrue(): void
    {
        $notes = null;
        $cartId = 1;
        $isNotesNull = true;

        $result = $this->orderNotes->getCurrentNotes($notes, $cartId, $isNotesNull);
        $this->assertNull($result);
    }

    public function testGetCurrentNotesThrowsNoSuchEntityException(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $notes = null;
        $cartId = 1;
        $isNotesNull = false;

        $this->cartIntegrationNoteRepositoryMock->expects($this->once())
            ->method('getByParentId')
            ->with($cartId)
            ->willThrowException(new NoSuchEntityException(__('No such entity')));

        $this->orderNotes->getCurrentNotes($notes, $cartId, $isNotesNull);
    }
}
