<?php

declare(strict_types=1);

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

        if ($jf->isArticleVisible($element->jf_visible_expression)) {
            return $buffer;
        }

        return '';
    }
}
