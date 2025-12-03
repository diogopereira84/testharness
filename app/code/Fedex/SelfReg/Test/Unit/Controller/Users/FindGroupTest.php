<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Controller\Users;

use Fedex\SelfReg\Controller\Users\FindGroup;
use Fedex\SelfReg\Model\FindGroupModel;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FindGroupTest extends TestCase
{
    private $resultJsonFactory;
    private $request;
    private $logger;
    private $findGroupModel;
    private $findGroup;

    protected function setUp(): void
    {
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->findGroupModel = $this->createMock(FindGroupModel::class);

        $this->findGroup = new FindGroup(
            $this->resultJsonFactory,
            $this->request,
            $this->logger,
            $this->findGroupModel
        );
    }

    public function testExecute()
    {
        $jsonResponse = $this->createMock(Json::class);
        $this->resultJsonFactory->expects($this->once())->method('create')->willReturn($jsonResponse);

        $this->assertEquals($jsonResponse, $this->findGroup->execute());
    }
}