<?php
namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Index;

use Fedex\Company\Controller\Adminhtml\Index\Upload;
use Magento\Backend\App\Action\Context;
use	Fedex\Company\Model\ImageUploader;
use Magento\Framework\Controller\ResultFactory;
use PHPUnit\Framework\TestCase;
use Magento\Backend\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\AuthorizationInterface;

use Exception;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class UploadTest extends TestCase
{

	protected $sessionMock;
 protected $resultFactory;
 protected $AuthorizationInterfaceMock;
 protected $objectManagerMock;
 protected $contextMock;
 protected $ImageUploaderMock;
 protected $uploadMock;
 protected function setUp(): void
    {
	

            $this->sessionMock = $this->getMockBuilder(Session::class)
            ->setMethods(['getName', 'setIsUrlNotice'])
            ->disableOriginalConstructor()
            ->getMock();

            $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

            $this->AuthorizationInterfaceMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMockForAbstractClass();
            
            $this->objectManagerMock = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
            $this->contextMock = $this->getMockBuilder(Context::class)
            ->setMethods([
                'getResultFactory',
                'getAuthorization',
                'getName',
                'getObjectManager',
                'getSession',
                'getSessionId',
                'getCookieLifetime',
                'getCookiePath',
                'getCookieDomain'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

            $this->contextMock->expects($this->any())
                ->method('getAuthorization')
                ->willReturn($this->AuthorizationInterfaceMock);

            $this->contextMock
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);

            $this->contextMock
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($this->sessionMock);

            $this->contextMock
            ->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $this->contextMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn('yogesh');

            $this->contextMock
            ->expects($this->any())
            ->method('getSessionId')
            ->willReturn('yogesh');
           
             $this->contextMock
            ->expects($this->any())
            ->method('getCookiePath')
            ->willReturnSelf();
            
            $this->contextMock
            ->expects($this->any())
            ->method('getCookieDomain')
            ->willReturn('stage3.com');

		$this->ImageUploaderMock = $this->getMockBuilder(ImageUploader::class)
			->disableOriginalConstructor()
			->setMethods(['saveFileToTmpDir'])
			->getMock();

        $this->uploadMock = new Upload(
            $this->contextMock,
            $this->ImageUploaderMock,
            $this->resultFactory,
        );
    }

	public function testeExecute()
	{
         $testMethod = new \ReflectionMethod( Upload::class, '_isAllowed');

             $testMethod->setAccessible(true);
             $testMethod->invoke($this->uploadMock);

            $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
            
        $this->resultFactory->expects($this->any())->method('create')->willReturn($result);
		$this->assertNull($this->uploadMock->execute());
	}

    public function testeExecuteWithException()
	{
        $exception = new Exception();
        $this->ImageUploaderMock->expects($this->any())->method('saveFileToTmpDir')->willThrowException($exception);
            $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();
            
        $this->resultFactory->expects($this->any())->method('create')->willReturn($result);
		$this->assertNull($this->uploadMock->execute());
	}
}
