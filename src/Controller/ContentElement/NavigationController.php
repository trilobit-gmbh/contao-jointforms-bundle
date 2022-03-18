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
 * @ContentElement(jointforms_navigation, category="texts")
 * // template="ce_jointforms_navigation"
 */
class NavigationController extends AbstractContentElementController
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
            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_navigation'][0]).' ###';

            return $objTemplate->parse();
        }

        return $this->getContent();
    }

    protected function getContent(): string
    {
        $buffer = '';

        $jf = new ConfigurationProvider('travelgrants');

        if (!empty($jf->config)) {
            foreach ($jf->config['items'] as $item) {
                if (empty($item['visible'])) {
                    continue;
                }

                $output = new ContentHyperlink(new ContentModel());
                $output->type = 'hyperlink';
                $output->url = $item['link'];
                $output->linkTitle = $item['title'];
                $output->cssID = ['', $item['class']];

                $buffer .= !empty($item = $output->generate()) ? $item : '';
            }
        }

        return $buffer;
    }
}
