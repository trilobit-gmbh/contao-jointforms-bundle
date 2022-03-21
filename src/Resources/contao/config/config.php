<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

use Trilobit\JointformsBundle\EventListener\LoadFormFieldListener;
use Trilobit\JointformsBundle\EventListener\ParseFrontendTemplateListener;
use Trilobit\JointformsBundle\EventListener\ProcessFormDataListener;

$GLOBALS['TL_HOOKS']['loadFormField'][] = [LoadFormFieldListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['processFormData'][] = [ProcessFormDataListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = [ParseFrontendTemplateListener::class, '__invoke'];
