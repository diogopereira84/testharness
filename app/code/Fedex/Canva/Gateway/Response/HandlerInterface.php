<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
namespace Fedex\Canva\Gateway\Response;

use GuzzleHttp\Psr7\Response;

interface HandlerInterface
{
    /**
     * Handles response
     *
     * @param Response $handlingSubject
     * @return UserToken
     */
    public function handle(Response $handlingSubject): UserToken;
}
