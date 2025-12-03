<?php

declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Plugin\Model;

use Fedex\Cart\Plugin\Model\ValidateCouponCodePlugin;
use Fedex\Cart\Helper\Data;
use Magento\SalesRule\Model\ValidateCouponCode;
use PHPUnit\Framework\TestCase;

class ValidateCouponCodePluginTest extends TestCase
{
    /**
     * @var Data
     */
    private Data $helperMock;

    /**
     * @var ValidateCouponCodePlugin
     */
    private ValidateCouponCodePlugin $plugin;

    /**
     * @var ValidateCouponCode
     */
    private ValidateCouponCode $subjectMock;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);

        $this->plugin = new ValidateCouponCodePlugin($this->helperMock);

        $this->subjectMock = $this->createMock(ValidateCouponCode::class);
    }

    /**
     * @covers \Fedex\Cart\Plugin\Model\ValidateCouponCodePlugin::__construct
     */
    public function testConstructorInitializesHelper(): void
    {
        $helperMock = $this->createMock(Data::class);

        $plugin = new ValidateCouponCodePlugin($helperMock);

        $ref = new \ReflectionClass($plugin);
        $prop = $ref->getProperty('helper');
        $prop->setAccessible(true);
        $actualHelper = $prop->getValue($plugin);

        $this->assertSame(
            $helperMock,
            $actualHelper,
            'Constructor must assign the Data helper to the private property'
        );
    }

    /**
     * @covers \Fedex\Cart\Plugin\Model\ValidateCouponCodePlugin::aroundExecute
     */
    public function testSkipsProceedWhenEnabled(): void
    {
        $this->helperMock
            ->expects($this->once())
            ->method('isMixedCartPromoErrorToggleEnabled')
            ->willReturn(true);

        $couponCodes = ['SAVE10', 'WELCOME'];

        $proceedCalled = false;
        $proceed = function (array $passedCodes, ?int $custId = null) use (&$proceedCalled) {
            $proceedCalled = true;
            return ['SHOULD_NOT_BE_RETURNED'];
        };

        $result = $this->plugin->aroundExecute(
            $this->subjectMock,
            $proceed,
            $couponCodes,
            42
        );

        $this->assertSame($couponCodes, $result);
        $this->assertFalse($proceedCalled, 'Proceed must not be called when toggle is enabled');
    }

    /**
     * @covers \Fedex\Cart\Plugin\Model\ValidateCouponCodePlugin::aroundExecute
     */
    public function testCallsProceedWhenDisabled(): void
    {
        $this->helperMock
            ->expects($this->once())
            ->method('isMixedCartPromoErrorToggleEnabled')
            ->willReturn(false);

        $couponCodes = ['FREESHIP'];
        $passedArgs = null;

        $expectedReturn = ['VALIDATED'];
        $proceed = function (array $codes, ?int $custId = null) use (&$passedArgs, $expectedReturn) {
            $passedArgs = ['codes' => $codes, 'custId' => $custId];
            return $expectedReturn;
        };

        $result = $this->plugin->aroundExecute(
            $this->subjectMock,
            $proceed,
            $couponCodes
        );

        $this->assertSame($expectedReturn, $result);
        $this->assertSame(
            ['codes' => $couponCodes, 'custId' => null],
            $passedArgs,
            'Proceed should be invoked with original parameters when toggle is disabled'
        );
    }

    /**
     * @covers \Fedex\Cart\Plugin\Model\ValidateCouponCodePlugin::aroundExecute
     */
    public function testCreatesNewArrayWhenEnabled(): void
    {
        $this->helperMock
            ->expects($this->once())
            ->method('isMixedCartPromoErrorToggleEnabled')
            ->willReturn(true);

        $originalCodes = ['code1' => 'SAVE10', 'code2' => 'WELCOME'];

        $proceed = function (array $codes) {
            return $codes;
        };

        $result = $this->plugin->aroundExecute(
            $this->subjectMock,
            $proceed,
            $originalCodes
        );

        $this->assertEquals(array_values($originalCodes), $result, 'The content of the arrays should match');
        $this->assertNotEquals(
            array_keys($originalCodes),
            array_keys($result),
            'The plugin should return a new array with reindexed keys'
        );
    }
}
