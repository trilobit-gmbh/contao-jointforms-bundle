<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\FrontendTemplate;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @Hook("parseFrontendTemplate")
 */
class ParseFrontendTemplateListener
{
    public function __invoke(string $buffer, string $templateName, FrontendTemplate $template): string
    {
        if (empty($template->jf_visible_expression)
            || empty($template->jf_environment)
        ) {
            return $buffer;
        }

        $jf = new ConfigurationProvider($template->jf_environment);

        if ($jf->isElementVisible($template->jf_visible_expression)) {
            return $buffer;
        }

        return '';
    }
}
