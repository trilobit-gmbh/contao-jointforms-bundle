<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement("jf_summary", category="jointforms", template="ce_jf_summary")
 */
class SummaryController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_summary'][0]).' ###';

            return $template->getResponse();
        }

        $jf = new ConfigurationProvider($model->jf_environment);

        if (empty($jf->config) || empty($jf->config['items'])) {
            return $template->getResponse();
        }

        $json = (!empty($jf->config['member']->jf_data) ? html_entity_decode($jf->config['member']->jf_data) : '');

        $template->error = [];

        if (!empty($json)) {
            try {
                $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                $template->error = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ];
            }
        } else {
            $json = new \stdClass();
        }

        $datimFormat = Config::get('datimFormat');

        $template->json = $json;

        $template->jf_data = (null !== $template->error
            ? $this->getContent($jf, $json, $datimFormat)
            : []
        );

        $template->jf_summary_general = $GLOBALS['TL_LANG']['MSC']['jf_summary_general'];

        $template->jf_last_modified_label = $GLOBALS['TL_LANG']['MSC']['jf_last_modified_label'];
        $template->jf_last_modified = Date::parse($datimFormat, $jf->config['member']->jf_last_modified);

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

        $multiFormGroupField = [];

        foreach ($jf->config['items'] as $item) {
            if (empty($item['visible'])
                || 'tl_form' !== $item['type']
            ) {
                continue;
            }

            $formKey = 'form'.$item['id'];

            if (!isset($json->{$formKey})) {
                continue;
            }

            $form = FormModel::findByPk($item['id']);
            $fields = FormFieldModel::findByPid(
                $item['id'],
                [
                    'order' => 'sorting',
                ]
            )->fetchAll();

            $items = [];
            $subItems = [];
            $fieldsets = [];

            $multiFormGroup = null;

            foreach ($fields as $key => $field) {
                if (!\in_array($field['type'], ['explanation', 'fieldsetStart', 'fieldsetStop', 'text', 'password', 'textarea', 'select', 'radio', 'checkbox', 'upload', 'range', 'conditionalselect', 'select_plus', 'textselect_plus', 'fileUpload_plus'], true)) {
                    continue;
                }

                if (!empty($field['invisible'])) {
                    continue;
                }

                if (!empty($field['noSummaryView'])) {
                    continue;
                }

                if (!empty($field['jf_visible_expression'])
                    && !$jf->isElementVisible($field['jf_visible_expression'])
                ) {
                    continue;
                }

                $expression = '';

                if ('fieldsetStart' === $field['type']) {
                    $fieldsets[] = $field;

                    if (!empty($field['multi_form_group'])) {
                        $multiFormGroup = $field['id'];

                        $fieldsets[array_key_last($fieldsets)]['isInMultiFormGroup'] = $multiFormGroup;

                        $items[$field['id'].'.0'] = [
                            'id' => $field['id'],
                            'type' => 'multi_form_group',
                            'name' => $field['name'],
                            'value' => $field['value'],
                            'label' => $field['label'],
                            'jf_label' => '',
                            'text' => $field['label'],
                            'subItems' => [],
                        ];
                        // $field;
                        $items[$field['id'].'.0']['type'] = 'multi_form_group';
                        $items[$field['id'].'.0']['text'] = $field['label'];
                        $items[$field['id'].'.0']['subItems'] = [];
                    }

                    if (!empty($field['isConditionalFormField'])
                        && !empty($field['conditionalFormFieldCondition'])
                    ) {
                        $fieldsets[array_key_last($fieldsets)]['isInMultiFormGroup'] = $multiFormGroup;
                        $fieldsets[array_key_last($fieldsets)]['conditionalFormFieldCondition'] = $field['conditionalFormFieldCondition'];
                    }

                    continue;
                }

                if ('fieldsetStop' === $field['type']) {
                    array_pop($fieldsets);
                    continue;
                }

                if (!empty($fieldsets)) {
                    if (!empty($fieldsets[array_key_last($fieldsets)]['isInMultiFormGroup'])
                        && !empty($fieldsets[array_key_last($fieldsets)]['conditionalFormFieldCondition'])
                    ) {
                        foreach (range(0, $json->{$formKey}->{'multi_form_size__'.$fieldsets[array_key_last($fieldsets)]['isInMultiFormGroup']} - 1) as $i) {
                            preg_match(
                                '/^(.*)(&&|\|\||!|==|!=|<|>|<=|>=)(.*)$/',
                                $fieldsets[array_key_last($fieldsets)]['conditionalFormFieldCondition'],
                                $conditionMatches
                            );

                            $expression = 'jointforms.'
                                .$formKey.'.'
                                .str_replace($conditionMatches[1], $conditionMatches[1].'__'.$i, $fieldsets[array_key_last($fieldsets)]['conditionalFormFieldCondition'])
                                .' ? true : false';

                            if ($jf->isElementVisible($expression)
                                && property_exists($json->{$formKey}, $field['name'].'__'.$i)
                            ) {
                                $multiFormGroupField[$multiFormGroup][$conditionMatches[1]][$field['name']][$i] = [
                                    'id' => $field['id'],
                                    'type' => $field['type'],
                                    'name' => $field['name'],
                                    'value' => $json->{$formKey}->{$field['name'].'__'.$i},
                                    'label' => $field['label'],
                                    'jf_label' => $field['jf_short_label'],
                                ];
                            }
                        }
                    }

                    if (!empty($fieldsets[array_key_last($fieldsets)]['conditionalFormFieldCondition'])) {
                        $expression = 'jointforms.'
                            .$formKey.'.'
                            .$fieldsets[array_key_last($fieldsets)]['conditionalFormFieldCondition']
                            .' ? true : false';

                        if (!$jf->isElementVisible($expression)) {
                            continue;
                        }
                    }
                }

                if (!empty($json->{$formKey}) && property_exists($json->{$formKey}, $field['name'].'__0')) {
                    $i = 0;

                    while (property_exists($json->{$formKey}, $field['name'].'__'.$i)) {
                        $subItems[$multiFormGroup.'.0'][$field['name']][$i] = [
                            'id' => $field['id'],
                            'type' => $field['type'],
                            'name' => $field['name'],
                            'value' => (!empty($value = $json->{$formKey}->{$field['name'].'__'.$i}) ? $value : null),
                            'label' => $field['label'],
                            'jf_label' => $field['jf_short_label'],
                        ];

                        if ('explanation' === $field['type']) {
                            $subItems[$multiFormGroup][$field['name']][$i]['text'] = $field['text'];
                        }

                        ++$i;
                    }

                    $subItems[$multiFormGroup.'.0']['__group_count__'] = $i;
                } else {
                    if (empty($json->{$formKey})) {
                        $json->{$formKey} = new \stdClass();
                    }

                    if (empty($json->{$formKey}->{$field['name']})) {
                        $json->{$formKey}->{$field['name']} = null;
                    }

                    $items[$field['sorting'].'.0'] = [
                        'id' => $field['id'],
                        'type' => $field['type'],
                        'name' => $field['name'],
                        'value' => (!empty($value = $json->{$formKey}->{$field['name']}) ? $value : null),
                        'label' => $field['label'],
                        'jf_label' => $field['jf_short_label'],
                    ];

                    if ('explanation' === $field['type']) {
                        $items[$field['sorting'].'.0']['text'] = $field['text'];
                    }
                }
            }

            if (isset($multiFormGroupField[$multiFormGroup])) {
                foreach ($multiFormGroupField[$multiFormGroup] as $source => $value) {
                    $subItems[$multiFormGroup.'.0'] = \array_slice(
                        $subItems[$multiFormGroup.'.0'],
                        0,
                        array_search($source, array_keys($subItems[$multiFormGroup.'.0']), true) + 1,
                        true
                    )
                        + $value
                        + \array_slice(
                            $subItems[$multiFormGroup.'.0'],
                            array_search($source, array_keys($subItems[$multiFormGroup.'.0']), true) + 1,
                            \count($subItems[$multiFormGroup.'.0']) - 1,
                            true
                        );
                }
            }

            if (!empty($subItems)) {
                foreach ($subItems as $fieldset => $fields) {
                    foreach (range(0, $fields['__group_count__'] - 1) as $key) {
                        unset($fields['__group_count__']);

                        $items[$fieldset]['subFields'][] = [
                            'type' => 'multi_form_group explanation',
                            'text' => str_replace(['##number##', '&#35;&#35;number&#35;&#35;'], (string) ($key + 1), $items[$fieldset]['text']),
                        ];

                        foreach ($fields as $field) {
                            if (isset($field[$key])) {
                                $items[$fieldset]['subFields'][] = $field[$key];
                            }
                        }
                    }

                    $items[$fieldset]['subFields'][] = [
                        'type' => 'multi_form_group explanation',
                        'text' => '',
                    ];
                }
            }

            $items[] = [
                'type' => 'jf_system',
                'name' => 'jf_form_complete',
                'value' => (isset($json->{$formKey}->jointforms_complete) && !empty($value = $json->{$formKey}->jointforms_complete) ? '✓' : '✕'),
                'label' => $GLOBALS['TL_LANG']['MSC']['jf_form_complete'],
                'jf_label' => '',
            ];

            $items[] = [
                'type' => 'jf_system',
                'name' => 'jf_form_complete_datim',
                'value' => (isset($json->{$formKey}->jointforms_complete_datim) && !empty($value = $json->{$formKey}->jointforms_complete_datim) ? Date::parse($datimFormat, $value) : null),
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
                    'jf_title' => $item['jf_title'] ?? '',
                ],
                'fields' => $items,
            ];
        }

        // echo '<pre>';
        // print_r($multiFormGroupField);
        // print_r($data);
        // echo '</pre>';
        // die();

        return $data;
    }
}
