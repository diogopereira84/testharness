<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Delivery\Test\Unit\Model;

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
     * @var $telephone
     */
    protected $telephone;
    
    /**
     * Person constructor.
     *
     * @param string $name
     * @param string $email
     */
    public function __construct(string $name, string $email, string $telephone)
    {
        $this->name = $name;
        $this->email  = $email;
        $this->telephone = $telephone;
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
            'telephone' => $this->telephone
        ];
    }
}
