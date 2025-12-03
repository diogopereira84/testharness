<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Note\Command;

use Fedex\Cart\Api\CartIntegrationNoteRepositoryInterface;
use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote\Collection;
use Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory;
use Fedex\CartGraphQl\Model\Note\Command\Save;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveTest extends TestCase
{
    private $cartIntegrationNoteRepositoryMock;
    private $cartIntegrationNoteFactoryMock;
    private $loggerMock;
    private $cartIntegrationNoteMock;
    private $quoteMock;
    private $collectionMock;
    private $save;

    protected function setUp(): void
    {
        $this->cartIntegrationNoteRepositoryMock = $this->createMock(CartIntegrationNoteRepositoryInterface::class);
        $this->cartIntegrationNoteFactoryMock = $this->createMock(CartIntegrationNoteInterfaceFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->cartIntegrationNoteMock = $this->createMock(IntegrationNote::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->collectionMock = $this->createMock(Collection::class);

        $this->save = new Save(
            $this->cartIntegrationNoteRepositoryMock,
            $this->cartIntegrationNoteFactoryMock,
            $this->loggerMock
        );
    }

    public function testExecuteSuccess(): void
    {
        $note = json_encode([
            "text" => "Text Example",
            "audit" => []
        ]);
        $cartId = 1;

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn($cartId);

        $this->cartIntegrationNoteRepositoryMock->expects($this->once())
            ->method('getByParentId')
            ->willReturn($this->cartIntegrationNoteMock);

        $this->cartIntegrationNoteMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->cartIntegrationNoteMock->expects($this->once())
            ->method('setParentId')
            ->with($cartId)
            ->willReturnSelf();

        $this->cartIntegrationNoteMock->expects($this->once())
            ->method('setNote')
            ->with($note)
            ->willReturnSelf();

        $this->cartIntegrationNoteRepositoryMock->expects($this->once())
            ->method('save')
            ->with($this->cartIntegrationNoteMock);

        $this->save->execute($this->quoteMock, $note);
    }

    public function testExecuteWithException(): void
    {
        $note = 'Test Note';
        $cartId = 123;

        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn($cartId);

        $this->cartIntegrationNoteRepositoryMock->expects($this->once())
            ->method('getByParentId')
            ->willReturn($this->cartIntegrationNoteMock);

        $this->cartIntegrationNoteMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $this->cartIntegrationNoteMock->expects($this->once())
            ->method('setParentId')
            ->with($cartId)
            ->willReturnSelf();

        $this->cartIntegrationNoteMock->expects($this->once())
            ->method('setNote')
            ->with($note)
            ->willReturnSelf();

        $this->cartIntegrationNoteRepositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException(new CouldNotSaveException(__('Could not save the note.')));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error while save order notes data:Could not save the note.');

        $this->save->execute($this->quoteMock, $note);
    }
}
