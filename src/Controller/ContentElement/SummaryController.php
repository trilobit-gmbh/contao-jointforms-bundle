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
use Contao\ContentHeadline;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\System;
use Contao\Template;
use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement(jointforms_form, category="texts")
 * // template="ce_jointforms_navigation"
 */
class SummaryController extends AbstractContentElementController
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
            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_summary'][0]).' ###';

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

        $buffer = '';

        $json = $jf->config['member']->jf_data;

        if (!empty($json)) {
            $json = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
        } else {
            $json = new \stdClass();
        }

        if (!empty($jf->config)) {
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

                $headline = new ContentHeadline(new ContentModel());
                $headline->type = 'headline';
                $headline->headline = $item['jf_title'] ?: $item['title'];
                $headline->hl = 'h2';

                $buffer .= '<section class="summary step'.$step++.' '.$form->alias.'">';

                $buffer .= !empty($headline = $headline->generate()) ? $headline : '';

                $buffer .= '<table>';
                foreach ($fields as $key => $field) {
                    if (!empty($field['invisible'])) {
                        continue;
                    }
                    if (!\in_array($field['type'], ['text', 'password', 'textarea', 'select', 'radio', 'checkbox', 'upload', 'range', 'conditionalselect', 'select_plus'], true)) {
                        continue;
                    }

                    $buffer .= '<tr>'
                        .'<th title="'.$field['name'].'">'.($field['jf_short_label'] ?: $field['label']).'</th>'
                        .'<td>'.(!empty($value = $json->{$formKey}->{$field['name']}) ? $value : '-/-').'</td>'
                        .'</tr>';
                }
                $buffer .= '</table>';
                $buffer .= '</section>';
            }
        }

        return $buffer;
    }
}
