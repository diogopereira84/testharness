<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Test\Unit\Plugin;

/**
 * Person class for Unit Testing
 *
 */
class Person
{
    /**
     * @var $name
     */
    protected $name;

    /**
     * @var $email
     */
    protected $email;

    /**
     * Person constructor.
     *
     * @param string $name
     * @param string $email
     */
    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email  = $email;
    }

    /**
     * Define __toArray for unit testing purpose
     *
     * @return array
     */
    public function __toArray()
    {
        return [
            'name' => $this->name,
            'email'  => $this->email,
        ];
    }
}

