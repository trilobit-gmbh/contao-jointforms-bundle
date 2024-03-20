<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Form;
use Contao\Widget;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @Hook("loadFormField")
 */
class LoadFormFieldListener extends ConfigurationProvider
{
    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): object
    {
        if (empty($form->jf_environment)) {
            return $widget;
        }

        $jf = new ConfigurationProvider($form->jf_environment);

        $widget->value = $jf->getFormValue($widget);

        return $widget;
    }
}
