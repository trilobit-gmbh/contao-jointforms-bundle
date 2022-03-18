<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-refresh-bundle
 */

namespace Trilobit\JointformsBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentHeadline;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Form;
use Contao\FormModel;
use Contao\System;
use Contao\Template;
use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\FormvalidationBundle\ModuleFormGenerator;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement(jointforms_form, category="texts")
 * // template="ce_jointforms_navigation"
 */
class FormController extends AbstractContentElementController
{
    public function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        return new Response($this->generate());
    }

    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_form'][0]).' ###';

            return $objTemplate->parse();
        }

        return $this->getContent();
    }

    protected function getContent(): string
    {
        $jf = new ConfigurationProvider('travelgrants');

        if (empty($jf->config)) {
            return '';
        }

        $model = FormModel::findByIdOrAlias($jf->currentForm);

        if (empty($model)) {
            return '';
        }

        $jf->page->title = $model->jf_title ?: $model->title;

        $buffer = '';

        $output = new ContentHeadline(new ContentModel());
        $output->type = 'headline';
        $output->headline = $model->title;
        $output->hl = 'h2';

        $buffer .= $output->generate();

        $model->typePrefix = 'ce_';
        $model->form = $model->id;

        $class = class_exists(ModuleFormGenerator::class) ? '\Trilobit\FormvalidationBundle\ModuleFormGenerator' : '\Contao\Form';

        $output = new $class($model);
        $output->id = $model->id;

        $buffer .= $output->generate();

        return $buffer;
    }
}
