<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Controller\Adminhtml\Index;

use Exception;
use PHPUnit\Framework\TestCase;
use Fedex\CIDPSG\Model\ImageUploader;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\CIDPSG\Controller\Adminhtml\Index\Upload;

/**
 * Test class for Upload
 */
class UploadTest extends TestCase
{
    protected $resultFactory;
    protected $jsonResult;
    /**
     * @var (\Magento\Framework\AuthorizationInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $authorizationInterfaceMock;
    protected $ImageUploaderMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $uploadMock;
    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->jsonResult = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->authorizationInterfaceMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['isAllowed'])
            ->getMockForAbstractClass();

        $this->ImageUploaderMock = $this->getMockBuilder(ImageUploader::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveFileToTmpDir'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->uploadMock = $this->objectManagerHelper->getObject(
            Upload::class,
            [
                'authorizationInterface' => $this->authorizationInterfaceMock,
                'imageUploader' => $this->ImageUploaderMock,
                'resultFactory' => $this->resultFactory
            ]
        );
    }

    /**
     * test execute method
     *
     * @return void
     */
    public function testeExecute()
    {
        $testMethod = new \ReflectionMethod(Upload::class, '_isAllowed');
        $testMethod->invoke($this->uploadMock);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->jsonResult);

        $this->assertNull($this->uploadMock->execute());
    }

    /**
     * test execute method with execption
     *
     * @return void
     */
    public function testeExecuteWithException()
    {
        $exception = new Exception();
        $this->ImageUploaderMock->expects($this->any())->method('saveFileToTmpDir')->willThrowException($exception);
        $this->resultFactory->expects($this->any())->method('create')->willReturn($this->jsonResult);

        $this->assertNull($this->uploadMock->execute());
    }
}
