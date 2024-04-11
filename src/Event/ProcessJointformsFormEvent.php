<?php

namespace Trilobit\JointformsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ProcessJointformsFormEvent extends Event
{
    private $jf;

    public function __construct($jf)
    {
        $this->jf = $jf;
    }
    
    public function getData(): array
    {
        return $this->jf;
    }
}