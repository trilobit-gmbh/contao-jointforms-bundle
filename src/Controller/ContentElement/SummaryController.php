<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

namespace Trilobit\JointformsBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Date;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\System;
use Contao\Template;
use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement("jf_summary", category="jointforms", template="ce_jf_summary")
 */
class SummaryController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_summary'][0]).' ###';

            return $template->getResponse();
        }

        $jf = new ConfigurationProvider($model->jf_environment);

        if (empty($jf->config) || empty($jf->config['items'])) {
            return $template->getResponse();
        }

        $json = html_entity_decode(!empty($jf->config['member']->jf_data) ? $jf->config['member']->jf_data : '');

        if (!empty($json)) {
            $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        } else {
            $json = new \stdClass();
        }

        $datimFormat = Config::get('datimFormat');

        $template->jf_data = $this->getContent($jf, $json, $datimFormat);

        $template->jf_summary_general = $GLOBALS['TL_LANG']['MSC']['jf_summary_general'];

        $template->jf_last_modified_label = $GLOBALS['TL_LANG']['MSC']['jf_last_modified_label'];
        $template->jf_last_modified = Date::parse($datimFormat, $json->last_modified);

        $template->jf_complete_label = $GLOBALS['TL_LANG']['MSC']['jf_complete_label'];
        $template->jf_complete = (!empty($jf->config['member']->jf_complete) ? '✓' : '✕');

        $template->jf_complete_datim_label = $GLOBALS['TL_LANG']['MSC']['jf_complete_datim_label'];
        $template->jf_complete_datim = (!empty($jf->config['member']->jf_complete) ? Date::parse($datimFormat, $jf->config['member']->jf_complete_datim) : '');

        return $template->getResponse();
    }

    protected function getContent(ConfigurationProvider $jf, $json, $datimFormat): array
    {
        $data = [];
        $step = 0;

        foreach ($jf->config['items'] as $item) {
            if (empty($item['visible']) || 'tl_form' !== $item['type']) { // || true === $item['submit']
                continue;
            }

            $formKey = 'form'.$item['id'];

            $form = FormModel::findByPk($item['id']);
            $fields = FormFieldModel::findByPid(
                $item['id'],
                [
                    'order' => 'sorting',
                ]
            )->fetchAll();

            $items = [];
            foreach ($fields as $key => $field) {
                if (!empty($field['invisible'])) {
                    continue;
                }

                if (!empty($field['jf_visible_expression']) && !$jf->isElementVisible($field['jf_visible_expression'])) {
                    continue;
                }

                if (!\in_array($field['type'], ['text', 'password', 'textarea', 'select', 'radio', 'checkbox', 'upload', 'range', 'conditionalselect', 'select_plus', 'textselect_plus', 'fileUpload_plus'], true)) {
                    continue;
                }

                $items[] = [
                    'type' => $field['type'],
                    'name' => $field['name'],
                    'value' => (!empty($value = $json->{$formKey}->{$field['name']}) ? $value : null),
                    'label' => $field['label'],
                    'jf_label' => $field['jf_short_label'],
                ];
            }

            $items[] = [
                'type' => 'jf_system',
                'name' => 'jf_form_complete',
                'value' => (!empty($value = $json->{$formKey}->jointforms_complete) ? '✓' : '✕'),
                'label' => $GLOBALS['TL_LANG']['MSC']['jf_form_complete'],
                'jf_label' => '',
            ];
            $items[] = [
                'type' => 'jf_system',
                'name' => 'jf_form_complete_datim',
                'value' => (!empty($value = $json->{$formKey}->jointforms_complete_datim) ? Date::parse($datimFormat, $value) : null),
                'label' => $GLOBALS['TL_LANG']['MSC']['jf_form_complete_datim'],
                'jf_label' => '',
            ];

            $data[] = [
                'form' => [
                    'step' => ++$step,
                    'id' => $item['id'],
                    'key' => $formKey,
                    'alias' => $form->alias,
                    'title' => $item['title'],
                    'jf_title' => $item['jf_title'],
                ],
                'fields' => $items,
            ];
        }

        return $data;
    }
}
