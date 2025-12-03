<?php
/**
 * @category     Fedex
 * @package      Fedex_FujitsuCore
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\GraphQl\Test\Unit\Model;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;

class RequestQueryValidatorTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Request\Http & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestHttpMock;
    /**
     * @var RequestQueryValidator
     */
    private RequestQueryValidator $instance;

    /**
     * @var SerializerInterface|MockObject
     */
    private SerializerInterface|MockObject $serializerMock;

    /**
     * @var RequestInterface|MockObject
     */
    private RequestInterface|MockObject $requestMock;

     /**
      * @var UploadToQuoteViewModele|MockObject
      */
    protected $uploadToQuoteViewModelMock;

    protected function setUp(): void
    {
        $this->serializerMock = $this->createMock(SerializerInterface::class);
        $this->requestHttpMock = $this->createMock(Http::class);
        $this->uploadToQuoteViewModelMock = $this->createMock(UploadToQuoteViewModel::class);

        $this->instance = new RequestQueryValidator(
            $this->serializerMock,
            $this->requestHttpMock,
            $this->uploadToQuoteViewModelMock
        );

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getContent', 'isPost', 'isGet'])
            ->getMockForAbstractClass();
    }

    public function testGraphQl(): void
    {
        $result = $this->instance->isGraphQl();
        static::assertSame($result, false);
    }

    public function testIsNotGraphQlRequest(): void
    {
        $this->requestMock->expects(static::once())
          ->method('isPost')
          ->willReturn(false);

        $this->requestMock->expects(static::once())
          ->method('isGet')
          ->willReturn(false);

        $result = $this->instance->isGraphQlRequest($this->requestMock);
        static::assertSame($result, false);
    }

    public function testIsGraphQlRequestPost(): void
    {
        $requestContentJson = '{"query":[{}]}';
        $unserializeData = ["query" => ["test-query" => []]];
        $this->requestMock->expects(static::once())
          ->method('isPost')
          ->willReturn(true);

        $this->requestMock->expects(static::once())
          ->method('getContent')
          ->willReturn($requestContentJson);

        $this->serializerMock->expects(static::once())
          ->method('unserialize')
          ->with($requestContentJson)
          ->willReturn($unserializeData);

        $result = $this->instance->isGraphQlRequest($this->requestMock);
        static::assertSame($result, true);
    }

    public function testIsGraphQlRequestGet(): void
    {
        $requestContentJson = '{"query":[{}]}';
        $unserializeData = ["query" => ["test-query" => []]];
        $this->requestMock->expects(static::once())
          ->method('isPost')
          ->willReturn(false);

        $this->requestMock->expects(static::once())
          ->method('isGet')
          ->willReturn(true);

        $this->requestMock->expects(static::once())
          ->method('getContent')
          ->willReturn($requestContentJson);

        $this->serializerMock->expects(static::once())
          ->method('unserialize')
          ->with($requestContentJson)
          ->willReturn($unserializeData);

        $result = $this->instance->isGraphQlRequest($this->requestMock);
        static::assertSame($result, true);
    }

  /**
   * Test isNegotiableQuoteGraphQlRequest
   *
   * @return void
   */
    public function testIsNegotiableQuoteGraphQlRequest(): void
    {
        $requestContent = '{"query": "updateNegotiableQuote"}';
        $this->requestMock->expects(static::any())
        ->method('isPost')
        ->willReturn(true);
        $this->requestMock->expects(static::any())
        ->method('isGet')
        ->willReturn(true);
        $this->uploadToQuoteViewModelMock->expects(static::any())
        ->method('isUploadToQuoteGloballyEnabled')
        ->willReturn(true);
        $this->requestMock->expects(static::any())
        ->method('getContent')
        ->willReturn($requestContent);
        $this->serializerMock->expects(static::any())
        ->method('unserialize')
        ->with($requestContent)
        ->willReturn(['query' => 'updateNegotiableQuote']);

        $this->assertTrue($this->instance->isNegotiableQuoteGraphQlRequest($this->requestMock, true));
    }

  /**
   * Test isNegotiableQuoteGraphQlRequest
   *
   * @return void
   */
    public function testIsNegotiableQuoteGraphQlRequestFalse(): void
    {
        $this->requestMock->expects(static::any())
        ->method('isPost')
        ->willReturn(false);
        $this->requestMock->expects(static::any())
        ->method('isGet')
        ->willReturn(false);
    
        $this->assertFalse($this->instance->isNegotiableQuoteGraphQlRequest($this->requestMock, true));
    }
}
