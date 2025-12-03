<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Cron;

use Fedex\GraphQl\Cron\CleanExpiredFuseToken;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Integration\Model\ResourceModel\Oauth\Token;
use PHPUnit\Framework\TestCase;

class CleanExpiredFuseTokenTest extends TestCase
{
    const EXPIRES_AT = '1970-01-01 04:00:00';

    /**
     * @var Token|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenResourceModelMock;

    /**
     * @var AdapterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $adapterInterfaceMock;

    /**
     * @var DateTimeFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTimeFactoryMock;

    /**
     * @var CleanExpiredFuseToken
     */
    protected CleanExpiredFuseToken $graphQlPlugin;

    protected function setUp(): void
    {
        $this->tokenResourceModelMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getMainTable'])
            ->getMockForAbstractClass();
        $this->dateTimeFactoryMock = $this->getMockBuilder(DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->onlyMethods(['delete', 'quoteInto'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->graphQlPlugin = new CleanExpiredFuseToken(
            $this->tokenResourceModelMock,
            $this->dateTimeFactoryMock
        );
    }

    public function testBeforeExecute()
    {
        $dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['gmtDate'])
            ->getMockForAbstractClass();
        $dateTimeMock->expects($this->once())->method('gmtDate')->willReturn(self::EXPIRES_AT);
        $this->dateTimeFactoryMock->expects($this->once())->method('create')->willReturn($dateTimeMock);

        $this->adapterInterfaceMock->expects($this->once())->method('delete');
        $this->adapterInterfaceMock->expects($this->exactly(3))->method('quoteInto')
            ->withConsecutive(
                ['user_type = ?', UserContextInterface::USER_TYPE_INTEGRATION],
                ['is_fuse = ?', 1],
                ['expires_at <= ?', self::EXPIRES_AT]
            )->willReturnOnConsecutiveCalls('user_type = 1', 'is_fuse = 1', 'expires_at <= '.self::EXPIRES_AT);

        $this->tokenResourceModelMock->expects($this->once())
            ->method('getConnection')->willReturn($this->adapterInterfaceMock);

        $this->tokenResourceModelMock->expects($this->once())
            ->method('getMainTable')->willReturn('table_name');

        $this->graphQlPlugin->execute();
    }
}
