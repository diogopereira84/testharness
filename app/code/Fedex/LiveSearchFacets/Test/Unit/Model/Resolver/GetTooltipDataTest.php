<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Rutvee Sojitra <rsojitra@mcfadyen.com>
 */
declare(strict_types=1);
namespace Fedex\LiveSearchFacets\Test\Unit\Model\Resolver;

use Fedex\LiveSearchFacets\Model\Resolver\GetTooltipData;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\LiveSearchFacets\Model\Resolver\DataProvider;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use PHPUnit\Framework\MockObject\MockObject;

class GetTooltipDataTest extends TestCase
{
    private object $getTooltipMock;
    private ObjectManager $objectManager;
    private ResolveInfo|MockObject $resolveInfo;
    private Context|MockObject $context;
    private Field|MockObject $field;
    private DataProvider|MockObject $dataProviderMock;

    public function testResolve() {
      $this->dataProviderMock = $this->getMockBuilder(DataProvider::class)
            ->onlyMethods(
                [
                    'getTooltipData',
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
       $this->field = $this->getMockBuilder(Field::class)
            ->disableOriginalConstructor()
            ->getMock();
       $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolveInfo = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = new ObjectManager($this);
         $this->getTooltipMock= $this->objectManager->getObject(
            GetTooltipData::class,
            [
                'dataProvider' =>  $this->dataProviderMock,
            ]
        );
     $this->dataProviderMock->expects($this->any())->method('getTooltipData')->willReturnSelf();
      $this->assertEquals(null, $this->getTooltipMock->resolve($this->field, $this->context ,$this->resolveInfo));
    }
}
