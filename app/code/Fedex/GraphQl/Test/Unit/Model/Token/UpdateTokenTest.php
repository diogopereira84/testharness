<?php
/**
 * @category     Fedex
 * @package      Fedex_GraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model\Token;

use Fedex\GraphQl\Model\Token\UpdateToken;
use Magento\Integration\Model\Oauth\Token;
use PHPUnit\Framework\TestCase;
use Fedex\GraphQl\Helper\Data;
use Magento\Integration\Model\ResourceModel\Oauth\Token as ResourceToken;

class UpdateTokenTest extends TestCase
{
    const EXPIRES_AT = '1970-01-01 04:00:00';

    /**
     * @var ResourceToken|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resourceTokenMock;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataMock;

    /**
     * @var Token|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenMock;

    /**
     * @var UpdateToken
     */
    protected UpdateToken $updateToken;

    protected function setUp(): void
    {
        $this->resourceTokenMock = $this->getMockBuilder(ResourceToken::class)
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dataMock = $this->getMockBuilder(Data::class)
            ->onlyMethods(['generateAccessTokenExpirationDate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->tokenMock = $this->getMockBuilder(Token::class)
            ->addMethods(['setIsFuse', 'setExpiresAt'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->updateToken = new UpdateToken(
            $this->resourceTokenMock,
            $this->dataMock
        );
    }

    public function testExecute()
    {
        $this->tokenMock->expects($this->once())->method('setIsFuse');
        $this->tokenMock->expects($this->once())->method('setExpiresAt');
        $this->resourceTokenMock->expects($this->once())->method('save')->willReturnSelf();
        $this->dataMock->expects($this->once())->method('generateAccessTokenExpirationDate')
            ->willReturn(self::EXPIRES_AT);

        $this->updateToken->execute($this->tokenMock);
    }
}
