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
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\System;
use Contao\Template;
use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement("jf_summary", category="texts", template="ce_jf_summary")
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

        $template->data = $this->getContent('travelgrants');

        return $template->getResponse();
    }

    protected function getContent($environment): array
    {
        $jf = new ConfigurationProvider($environment);

        if (empty($jf->config) || empty($jf->config['items'])) {
            return [];
        }

        $json = $jf->config['member']->jf_data;

        if (!empty($json)) {
            $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        } else {
            $json = new \stdClass();
        }

        $data = [];
        $step = 0;

        foreach ($jf->config['items'] as $item) {
            if (empty($item['visible']) || 'tl_form' !== $item['type'] || true === $item['submit']) {
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

                if (!\in_array($field['type'], ['text', 'password', 'textarea', 'select', 'radio', 'checkbox', 'upload', 'range', 'conditionalselect', 'select_plus'], true)) {
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
