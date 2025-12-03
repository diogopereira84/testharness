<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\EnhancedProfile\Test\Unit\Controller\Account;

/**
 * SampleData class for Unit Testing
 *
 */
class SampleData
{
    /**
     * @var $name
     */
    public $output;
    
    /**
     * SampleData constructor.
     *
     * @param string $output
     */
    public function __construct(string $output)
    {
        $this->output = $output;
    }
}
