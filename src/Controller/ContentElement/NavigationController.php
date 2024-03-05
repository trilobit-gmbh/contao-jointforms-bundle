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
use Contao\ContentHyperlink;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\System;
use Contao\Template;
use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement("jf_navigation", category="jointforms", template="ce_jf_navigation")
 */
class NavigationController extends AbstractContentElementController
{
    public function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_navigation'][0]).' ###';

            return $template->getResponse();
        }

        $template->data = $this->getContent($model->jf_environment);

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

        $data = [];

        $submitId = null;
        $submitVisibility = true;

        foreach ($jf->config['items'] as $key => $item) {
            if (empty($item['visible'])) {
                continue;
            }

            if ('tl_form' === $item['type']) {
                if (\array_key_exists('submit', $item)) {
                    $submitId = $key;
                } elseif ('complete' !== $item['state']) {
                    $submitVisibility = false;
                }
            }

            $output = new ContentHyperlink(new ContentModel());
            $output->type = 'hyperlink';
            $output->url = $item['link'];
            $output->linkTitle = $item['title'];

            $output->cssID = ['', ''];
            if (\array_key_exists('class', $item)) {
                $output->cssID = ['', $item['class']];
            }

            $data[$key] = !empty($item = $output->generate()) ? $item : '';
        }

        if (!$submitVisibility) {
            unset($data[$submitId]);
        }

        return $data;
    }
}
