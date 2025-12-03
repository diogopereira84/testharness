<?php
declare(strict_types=1);

namespace Fedex\FXOPricing\Test\Unit\Model;

use Fedex\FXOPricing\Api\Data\AlertCollectionInterface;
use Fedex\FXOPricing\Api\Data\AlertInterface;
use Fedex\FXOPricing\Api\Data\AlertInterfaceFactory;
use Fedex\FXOPricing\Api\Data\AlertCollectionInterfaceFactory;
use Fedex\FXOPricing\Model\RateAlertBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for RateAlertBuilder
 */
class RateAlertBuilderTest extends TestCase
{
    /**
     * @var RateAlertBuilder
     */
    private RateAlertBuilder $rateAlertBuilder;

    /**
     * @var AlertInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $alertFactoryMock;

    /**
     * @var AlertCollectionInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $alertCollectionFactoryMock;

    /**
     * @var AlertCollectionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $alertCollectionMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->alertFactoryMock = $this->createMock(AlertInterfaceFactory::class);
        $this->alertCollectionFactoryMock = $this->createMock(AlertCollectionInterfaceFactory::class);
        $this->alertCollectionMock = $this->getMockBuilder(AlertCollectionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['addItem'])
            ->getMockForAbstractClass();

        $this->alertCollectionFactoryMock
            ->method('create')
            ->willReturn($this->alertCollectionMock);

        $this->rateAlertBuilder = new RateAlertBuilder(
            $this->alertFactoryMock,
            $this->alertCollectionFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testBuild(): void
    {
        $alerts = [
            ['code' => 'CODE1', 'message' => 'Message 1', 'alertType' => 'Type1']
        ];

        $alertMocks = [];
        foreach ($alerts as $alert) {
            $alertMock = $this->createMock(AlertInterface::class);
            $alertMock->expects($this->once())->method('setCode')->with($alert['code']);
            $alertMock->expects($this->once())->method('setMessage')->with($alert['message']);
            $alertMock->expects($this->once())->method('setAlertType')->with($alert['alertType']);
            $alertMocks[] = $alertMock;

            $this->alertFactoryMock
                ->expects($this->atLeastOnce())
                ->method('create')
                ->willReturn($alertMock);
        }

        $this->alertCollectionMock
            ->expects($this->exactly(count($alerts)))
            ->method('addItem')
            ->withConsecutive(...array_map(fn($mock) => [$mock], $alertMocks));

        $result = $this->rateAlertBuilder->build($alerts);

        $this->assertSame($this->alertCollectionMock, $result);
    }
}
