<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Model;

use Fedex\OKTA\Model\Backend\LoginHandlerFactory;
use Fedex\OKTA\Model\Config\Backend as OktaHelper;
use Fedex\OKTA\Model\Oauth\OktaTokenInterface;
use Fedex\OKTA\Model\Oauth\PostbackValidatorInterface;
use Fedex\OKTA\Model\Oauth\UrlBuilderInterface;
use Magento\Framework\ObjectManager\ContextInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * This class was introduced so the number of constructor parameters was decreased.
 */
class LoginContext implements ContextInterface
{
    /**
     * @param LoginHandlerFactory $loginHandlerFactory
     * @param OktaHelper $oktaHelper
     * @param PostbackValidatorInterface $postbackValidator
     * @param UrlBuilderInterface $urlBuilder
     * @param UrlInterface $urlInterface
     * @param OktaTokenInterface $oktaToken
     * @param LoggerInterface $logger
     */
    public function __construct(
        private LoginHandlerFactory $loginHandlerFactory,
        private OktaHelper $oktaHelper,
        private PostbackValidatorInterface $postbackValidator,
        private UrlBuilderInterface $urlBuilder,
        private UrlInterface $urlInterface,
        private OktaTokenInterface $oktaToken,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Return login handler instance
     *
     * @return LoginHandlerFactory
     */
    public function getLoginHandlerFactory(): LoginHandlerFactory
    {
        return $this->loginHandlerFactory;
    }

    /**
     * Return okta helper instance
     *
     * @return OktaHelper
     */
    public function getOktaHelper(): OktaHelper
    {
        return $this->oktaHelper;
    }

    /**
     * Return postback validator instance
     *
     * @return PostbackValidatorInterface
     */
    public function getPostbackValidator(): PostbackValidatorInterface
    {
        return $this->postbackValidator;
    }

    /**
     * Return url builder instance
     *
     * @return UrlBuilderInterface
     */
    public function getUrlBuilder(): UrlBuilderInterface
    {
        return $this->urlBuilder;
    }

    /**
     * Return url instance
     *
     * @return UrlInterface
     */
    public function getUrlInterface(): UrlInterface
    {
        return $this->urlInterface;
    }

    /**
     * Return okta Token instance
     *
     * @return OktaTokenInterface
     */
    public function getOktaToken(): OktaTokenInterface
    {
        return $this->oktaToken;
    }

    /**
     * Return logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
