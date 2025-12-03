<?php
/**
 * @category  Fedex
 * @package   Fedex_Company
 * @author    Jonatan Santos <jonatan.santos.osv@fedex.com>
 * @copyright 2023 Fedex
 */
declare(strict_types=1);

namespace Fedex\Company\Test\Unit\Model\Company\Source\Mask;

use Fedex\Company\Model\Company\Source\OptionInterface;
use InvalidArgumentException;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Fedex\Company\Model\Company\Source\Mask\NoOption;
use Fedex\Company\Model\Company\Source\Mask\LettersOnlyOption;
use Fedex\Company\Model\Company\Source\Mask\Collection;

class CollectionTest extends TestCase
{
    /**
     * Label key
     */
    private const LABEL_KEY = 'label';

    /**
     * Value key
     */
    private const VALUE_KEY = 'value';

    /**
     * Label no option
     */
    private const LABEL_NO_OPTION = 'No';

    /**
     * Value no option
     */
    private const VALUE_NO_OPTION = null;

    /**
     * Label letters option
     */
    private const LABEL_LETTERS_OPTION = 'Letters Only';

    /**
     * Value letters option
     */
    private const VALUE_LETTERS_OPTION = 'validate-alpha';

    /**
     * Options as array
     */
    private const OPTIONS_ARRAY = [
        [
            self::LABEL_KEY => self::LABEL_NO_OPTION,
            self::VALUE_KEY => self::VALUE_NO_OPTION,
        ],
        [
            self::LABEL_KEY => self::LABEL_LETTERS_OPTION,
            self::VALUE_KEY => self::VALUE_LETTERS_OPTION,
        ],
    ];

    /**
     * @var EntityFactoryInterface|MockObject
     */
    private EntityFactoryInterface $entityFactoryMock;

    /**
     * @var NoOption|MockObject
     */
    private NoOption $noOptionMock;

    /**
     * @var LettersOnlyOption|MockObject
     */
    private LettersOnlyOption $lettersOnlyOptionMock;

    /**
     * @var Collection
     */
    private Collection $collection;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->entityFactoryMock = $this->getMockForAbstractClass(
            EntityFactoryInterface::class
        );
        $this->noOptionMock = $this->createMock(
            NoOption::class
        );
        $this->lettersOnlyOptionMock = $this->createMock(
            LettersOnlyOption::class
        );
        $this->noOptionMock->expects($this->atMost(2))
            ->method('getData')
            ->withConsecutive(
                [self::LABEL_KEY], [self::VALUE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                self::LABEL_NO_OPTION,
                self::VALUE_NO_OPTION
            );
        $this->lettersOnlyOptionMock->expects($this->atMost(2))
            ->method('getData')
            ->withConsecutive(
                [self::LABEL_KEY], [self::VALUE_KEY]
            )
            ->willReturnOnConsecutiveCalls(
                self::LABEL_LETTERS_OPTION,
                self::VALUE_LETTERS_OPTION
            );
        $this->collection = new Collection(
            $this->entityFactoryMock,
            [
                $this->noOptionMock,
                $this->lettersOnlyOptionMock,
            ]
        );
    }

    /**
     * Test object construction with invalid option Type
     *
     * @return void
     */
    public function testInvalidOptionType(): void
    {
        $this->expectExceptionMessage(
            sprintf(
                'Instance of the %s is expected, got non object instead.',
                OptionInterface::class
            )
        );
        $this->expectException(InvalidArgumentException::class);
        new Collection(
            $this->entityFactoryMock,
            [
                $this->noOptionMock,
                $this->lettersOnlyOptionMock,
                []
            ]
        );
    }

    /**
     * Test object construction with invalid option object
     *
     * @return void
     */
    public function testInvalidOptionObject(): void
    {
        $this->expectExceptionMessage(
            sprintf(
                'Instance of the %s is expected, got %s instead.',
                OptionInterface::class,
                DataObject::class,
            )
        );
        $this->expectException(InvalidArgumentException::class);
        new Collection(
            $this->entityFactoryMock,
            [
                $this->noOptionMock,
                $this->lettersOnlyOptionMock,
                new DataObject()
            ]
        );
    }

    /**
     * Test toOptionArray method
     *
     * @return void
     */
    public function testToOptionArray(): void
    {
        $this->assertEquals(
            self::OPTIONS_ARRAY,
            $this->collection->toOptionArray()
        );
    }
}
