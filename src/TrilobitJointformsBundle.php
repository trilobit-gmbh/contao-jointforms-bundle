<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

namespace Trilobit\JointformsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Trilobit\JointformsBundle\DependencyInjection\JointformsExtension;

/**
 * Configures the trilobit jointforms bundle.
 *
 * @author trilobit GmbH <https://github.com/trilobit-gmbh>
 */
class TrilobitJointformsBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new JointformsExtension();
    }
}
