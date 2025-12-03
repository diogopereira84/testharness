<?php

namespace Fedex\OktaMFTF\Test\Unit\Setup\Patch\Data;

use Exception;
use Magento\User\Model\UserFactory;
use Magento\User\Model\User;
use Magento\Framework\App\Config\Storage\Writer;
use Magento\Setup\Module\DataSetup;
use Magento\Framework\Logger\LoggerProxy;
use Fedex\OktaMFTF\Setup\Patch\Data\CreateMFTFAdminUser;
use PHPUnit\Framework\TestCase;

class CreateMFTFAdminUserTest extends TestCase
{
    protected $userFactoryMock;
    protected $userMock;
    /**
     * @var (\Magento\Framework\App\Config\Storage\Writer & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $writeMock;
    /**
     * @var (\Magento\Setup\Module\DataSetup & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataSetupMock;
    protected $loggerProxyMock;
    protected $dataPatch;
    public function setUp(): void
    {
        $this->userFactoryMock = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userMock = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->writeMock = $this->getMockBuilder(Writer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataSetupMock = $this->getMockBuilder(DataSetup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerProxyMock = $this->getMockBuilder(LoggerProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataPatch = $this->getMockForAbstractClass(
            CreateMFTFAdminUser::class,
            [
                'moduleDataSetup' => $this->dataSetupMock,
                'userFactory' => $this->userFactoryMock,
                'writer' => $this->writeMock,
                'logger' => $this->loggerProxyMock
            ]
        );
    }

    public function testApplySuccess()
    {
        $this->userMock->expects($this->atLeast(2))->method('getId')->willReturn(2);
        $this->userFactoryMock->expects($this->once())->method('create')->willReturn($this->userMock);
        $this->assertEquals(null, $this->dataPatch->apply());
    }

    public function testApplyError()
    {
        $this->userMock->expects($this->once())->method('save')->will($this->throwException(new Exception()));
        $this->userFactoryMock->expects($this->once())->method('create')->willReturn($this->userMock);
        $this->loggerProxyMock->expects($this->once())->method('critical');
        $this->assertEquals(null, $this->dataPatch->apply());
    }

    public function testGetAliases()
    {
        $this->assertEquals([], $this->dataPatch->getAliases());
    }

    public function testGetDependencies()
    {
        $this->assertEquals([], $this->dataPatch->getDependencies());
    }
}
