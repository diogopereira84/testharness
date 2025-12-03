<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Fedex\GraphqlDocs\ViewModel\Admin;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\UrlInterface;

/**
 * View model for GraphQL schema browser
 */
class GraphQLSchemaBrowser implements ArgumentInterface
{
    /**
     * Constructor
     *
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private UrlInterface $urlBuilder
    ) {
    }

    /**
     * Get GraphQL endpoint URL
     *
     * @return string
     */
    public function getGraphQLEndpointUrl(): string
    {
        return rtrim($this->urlBuilder->getBaseUrl(), '/') . '/graphql';
    }
}

