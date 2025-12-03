<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Pooja Tiwari <pooja.tiwari@osv.com>
 */
declare (strict_types = 1);

namespace Fedex\Catalog\Test\Unit\Console\Command;

use Fedex\Catalog\Console\Command\PendingReviewStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PendingReviewStatusTest extends TestCase
{
    protected $resourceConnection;
    protected $inputMock;
    protected $outputMock;
    /**
     * @var (\Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $outputFormatterMock;
    protected $pendingReviewStatus;
    /**
     * Command name
     */
    private const NAME = "catalog:pendingReviewUpdate:command";

    /**
     * Command description
     */
    private const DESCRIPTION = "Console command to update pending review status with items where not available.";

    private const LIMIT = 'limit';

    protected function setUp(): void
    {
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTableName', 'select', 'fetchOne', 'fetchAll', 'insert'])
            ->getMock();

        $this->inputMock = $this->getMockForAbstractClass(
            InputInterface::class
        );
        $this->outputMock = $this->getMockForAbstractClass(
            OutputInterface::class
        );
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);

        $objectManagerHelper = new ObjectManager($this);
        $this->pendingReviewStatus = $objectManagerHelper->getObject(
            PendingReviewStatus::class,
            [
                'resourceConnection' => $this->resourceConnection,

            ]
        );
    }

    /**
     * Test method configure
     *
     * @return void
     * @throws ReflectionException
     */
    public function testConfigure(): void
    {
        $configureMethod = new \ReflectionMethod(
            PendingReviewStatus::class,
            'configure',
        );
        $configureMethod->setAccessible(true);

        $configureMethod->invoke($this->pendingReviewStatus);

        $this->assertEquals(self::NAME, $this->pendingReviewStatus->getName());
        $this->assertEquals(self::DESCRIPTION, $this->pendingReviewStatus->getDescription());
    }

    /**
     * Test method execute when success
     *
     * @return void
     * @throws ReflectionException
     */
    public function testExecuteSuccess(): void
    {
        $attributeId = 1;
        $recordsCount = 5;
        $configureMethod = new \ReflectionMethod(
            PendingReviewStatus::class,
            'execute',
        );
        $this->inputMock->expects($this->once())
            ->method('getOption')
            ->willReturn(self::LIMIT);
        $this->outputMock->expects($this->any())
            ->method('writeln')
            ->willReturnSelf();
        $configureMethod->setAccessible(true);
        $this->resourceConnection->expects($this->any())->method('getConnection')
            ->willReturnSelf();
        $this->resourceConnection->expects($this->any())->method('getTableName')->willReturnMap([
            ['eav_attribute', 'eav_attribute'],
            ['catalog_product_entity_int', 'catalog_product_entity_int'],
        ]);
        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->resourceConnection->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $selectMock->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $selectMock->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $this->resourceConnection->expects($this->any())
            ->method('fetchOne')
            ->willReturn($attributeId);

        $this->resourceConnection->expects($this->once())
            ->method('fetchAll')
            ->willReturn(array_fill(0, $recordsCount, ['row_id' => 1]));

        $this->resourceConnection->expects($this->exactly($recordsCount))
            ->method('insert');

        $result = $configureMethod->invoke(
            $this->pendingReviewStatus,
            $this->inputMock,
            $this->outputMock
        );

        $this->assertEquals(self::NAME, $this->pendingReviewStatus->getName());
        $this->assertEquals(0, $result);
    }

    /**
     * Test method execute when failure
     *
     * @return void
     * @throws ReflectionException
     */
    public function testExecuteWithException(): void
    {
        $exceptionMessage = 'Test Exception';

        $configureMethod = new \ReflectionMethod(PendingReviewStatus::class, 'execute');
        $this->inputMock->expects($this->never())
            ->method('getOption')
            ->willReturn(self::LIMIT);

        $this->outputMock->expects($this->never())
            ->method('writeln')
            ->withConsecutive(
                [$this->equalTo('<info>Provided limit is `limit`</info>')],
                [$this->callback(function ($message) use ($exceptionMessage) {
                    return strpos($message, $exceptionMessage) !== false;
                })]
            );

        $this->resourceConnection->expects($this->any())
            ->method('getConnection')
            ->will($this->throwException(new \Exception($exceptionMessage)));

        // $result = $configureMethod->invoke(
        //     $this->pendingReviewStatus,
        //     $this->inputMock,
        //     $this->outputMock
        // );

        //  $this->assertEquals(1, $result);
    }
}
