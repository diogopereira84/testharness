<?php
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\Quote\IntegrationNote\Command;

use Fedex\Cart\Model\Quote\IntegrationNote;
use Fedex\Cart\Model\Quote\IntegrationNote\Command\GetByParentId;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class GetByParentIdTest
 *
 * Unit test for GetByParentId
 */
class GetByParentIdTest extends TestCase
{
    /**
     * @var GetByParentId
     */
    private GetByParentId $getByParentId;

    /**
     * @var MockObject|IntegrationNoteResourceModel
     */
    private MockObject $integrationNoteResourceMock;

    /**
     * @var MockObject|IntegrationNote
     */
    private MockObject $integrationNoteMock;

    /**
     * @var MockObject|IntegrationNoteFactory
     */
    private MockObject $cartIntegrationNoteFactoryMock;

    protected function setUp(): void
    {
        $this->integrationNoteResourceMock = $this->createMock(IntegrationNoteResourceModel::class);

        $this->integrationNoteMock = $this->createMock(IntegrationNote::class);

        $this->cartIntegrationNoteFactoryMock = $this->createMock(\Fedex\Cart\Api\Data\CartIntegrationNoteInterfaceFactory::class);
        $this->cartIntegrationNoteFactoryMock->method('create')->willReturn($this->integrationNoteMock);

        $this->getByParentId = new GetByParentId(
            $this->integrationNoteResourceMock,
            $this->cartIntegrationNoteFactoryMock
        );
    }

    public function testExecute(): void
    {
        $cartIntegrationNoteParentId = 1;
        $this->integrationNoteResourceMock
            ->expects($this->once())
            ->method('load')
            ->with(
                $this->integrationNoteMock,
                $cartIntegrationNoteParentId,
                IntegrationNote::PARENT_ID
            );

        $result = $this->getByParentId->execute($cartIntegrationNoteParentId);
        $this->assertSame($this->integrationNoteMock, $result);
    }
}
