<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

use Trilobit\JointformsBundle\Controller\ContentElement\FormController;
use Trilobit\JointformsBundle\Controller\ContentElement\NavigationController;
use Trilobit\JointformsBundle\Controller\ContentElement\SummaryController;
use Trilobit\JointformsBundle\EventListener\LoadFormFieldListener;
use Trilobit\JointformsBundle\EventListener\ParseFrontendTemplateListener;
use Trilobit\JointformsBundle\EventListener\ProcessFormDataListener;

/*
$GLOBALS['TL_CTE']['jointforms'] = [
    'jf_summary' => SummaryController::class,
    'jf_navigation' => NavigationController::class,
    'jf_form' => FormController::class,
];
*/

$GLOBALS['TL_HOOKS']['loadFormField'][] = [LoadFormFieldListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['processFormData'][] = [ProcessFormDataListener::class, '__invoke'];
$GLOBALS['TL_HOOKS']['parseFrontendTemplate'][] = [ParseFrontendTemplateListener::class, '__invoke'];
