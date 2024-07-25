<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

#[\AllowDynamicProperties]
class JointformsEvent extends Event
{
    public const JF_PROCESS_FORM = 'jf.process_form';

    public function __construct(ConfigurationProvider $jf)
    {
        $this->jf = $jf;
    }
}
