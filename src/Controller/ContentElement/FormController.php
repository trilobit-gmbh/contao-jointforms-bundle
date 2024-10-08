<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Form;
use Contao\FormModel;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\FormvalidationBundle\ModuleFormGenerator;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement("jf_form", category="jointforms", template="ce_jf_form")
 */
class FormController extends AbstractContentElementController
{
    public function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_form'][0]).' ###';

            return $template->getResponse();
        }

        $template->data = $this->getContent($model->jf_environment);

        $template->title = $template->data['title'];
        $template->jf_title = $template->data['jf_title'];
        $template->form = $template->data['form'];
        $template->json = $template->data['json'];

        return $template->getResponse();
    }

    protected function getContent($environment): array
    {
        $jf = new ConfigurationProvider($environment);

        if (empty($jf->config)
            || empty($jf->config['items'])
        ) {
            return [];
        }

        $json = !empty($jf->config['member']->jf_data)
            ? html_entity_decode($jf->config['member']->jf_data)
            : '';

        if (!empty($json)) {
            $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        }

        $model = FormModel::findByIdOrAlias($jf->currentForm);

        if (empty($model)) {
            return [];
        }

        $model->typePrefix = 'ce_';
        $model->form = $model->id;

        $class = class_exists(ModuleFormGenerator::class) ? '\Trilobit\FormvalidationBundle\ModuleFormGenerator' : '\Contao\Form';

        $output = new $class($model);
        $output->id = $model->id;

        return [
            'title' => $model->title,
            'jf_title' => $model->jf_title,
            'form' => $output->generate(),
            'json' => json_encode($json->{'form'.$jf->currentForm} ?? ''),
            'member' => $jf->config['member'],
        ];
    }
}
