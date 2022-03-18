<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

namespace Trilobit\JointformsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @Hook("parseFrontendTemplate")
 */
class ParseFrontendTemplateListener
{
    public function __invoke($buffer, $template, $element): string
    {
        if (empty($element->jf_visible_expression)) {
            return $buffer;
        }

        $jf = new ConfigurationProvider('travelgrants');

        if ($jf->isElementVisible($element->jf_visible_expression)) {
            return $buffer;
        }

        return '';
    }
}
