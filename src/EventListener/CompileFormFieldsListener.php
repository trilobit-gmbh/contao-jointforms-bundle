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
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @Hook("compileFormFields")
 */
class CompileFormFieldsListener extends ConfigurationProvider
{
    public function __invoke(array $fields, $formId, Form $form): array
    {
        if (empty($form->jf_environment)) {
            return $fields;
        }

        $jf = new ConfigurationProvider($form->jf_environment);

        foreach ($fields as $key => $field) {
            if (!empty($field->jf_visible_expression)
                && !$jf->isElementVisible($field->jf_visible_expression)
            ) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }
}
