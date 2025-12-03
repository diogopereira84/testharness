<?php

declare(strict_types=1);

namespace Fedex\GraphqlDocs\Test\Unit\ViewModel\Admin;

use Fedex\GraphqlDocs\ViewModel\Admin\GraphQLSchemaBrowser;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class GraphQLSchemaBrowserTest extends TestCase
{
    public function testGetGraphQLEndpointUrlAppendsGraphql()
    {
        $urlBuilderMock = $this->createMock(UrlInterface::class);
        $urlBuilderMock->method('getBaseUrl')->willReturn('https://dev.office.fedex.com.com/');

        $viewModel = new GraphQLSchemaBrowser($urlBuilderMock);
        $this->assertEquals('https://dev.office.fedex.com.com/graphql', $viewModel->getGraphQLEndpointUrl());
    }

    public function testGetGraphQLEndpointUrlHandlesNoTrailingSlash()
    {
        $urlBuilderMock = $this->createMock(UrlInterface::class);
        $urlBuilderMock->method('getBaseUrl')->willReturn('https://dev.office.fedex.com.com');

        $viewModel = new GraphQLSchemaBrowser($urlBuilderMock);
        $this->assertEquals('https://dev.office.fedex.com.com/graphql', $viewModel->getGraphQLEndpointUrl());
    }
}

