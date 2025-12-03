<?php
/**
 * @category    Fedex
 * @package     Fedex_OktaMFTF
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OktaMFTF\Test\Unit\Model\Config\Adminhtml\Attribute\Source;

use Magento\User\Model\ResourceModel\User\CollectionFactory;
use Magento\User\Model\User;
use Fedex\OktaMFTF\Model\Config\Adminhtml\Attribute\Source\Admin;
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase
{
    private const TO_OPTION_ARRAY_LABEL = 'label';
    private const TO_OPTION_ARRAY_VALUE = 'value';

    public function testToOptionArray()
    {
        $userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUserId', 'getName'])
            ->getMock();
        $collectionMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userMock->expects($this->exactly(2))
            ->method('getUserId')
            ->willReturnOnConsecutiveCalls(1, 2);

        $userMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls(
                'Test 01',
                'Test 02'
            );

        $collectionMock->expects($this->once())->method('create')->willReturn([$userMock, $userMock]);
        $admin = new Admin($collectionMock);
        $this->assertEquals([
            [self::TO_OPTION_ARRAY_VALUE => '0', self::TO_OPTION_ARRAY_LABEL => __('None')],
            [self::TO_OPTION_ARRAY_VALUE => '1', self::TO_OPTION_ARRAY_LABEL => __('Test 01')],
            [self::TO_OPTION_ARRAY_VALUE => '2', self::TO_OPTION_ARRAY_LABEL => __('Test 02')]
        ], $admin->toOptionArray());
    }
}
