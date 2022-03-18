<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-refresh-bundle
 */

namespace Trilobit\JointformsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @Hook("loadFormField")
 */
class LoadFormFieldListener extends ConfigurationProvider
{
    public function __invoke($objWidget, $formId, $arrData, $that): object
    {
        $jf = new ConfigurationProvider('travelgrants');

        $objWidget->value = $jf->getFormValue($objWidget);

        return $objWidget;
    }
}
