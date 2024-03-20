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
use Contao\Controller;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

/**
 * @ContentElement("jf_redirect", category="jointforms", template="ce_jf_redirect")
 */
class RedirectController extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): Response
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request
            && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)
        ) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### '.strtoupper($GLOBALS['TL_LANG']['CTE']['jointforms'][0].' '.$GLOBALS['TL_LANG']['CTE']['jf_redirect'][0]).' ###';

            return $template->getResponse();
        }

        if (empty($model->jf_environment)) {
            return $template->getResponse();
        }

        $jf = new ConfigurationProvider($model->jf_environment);

        if (!$jf->isElementVisible($model->jf_visible_expression)) {
            return $template->getResponse();
        }

        $page = PageModel::findByPk($model->jf_jumpTo);

        if (null === $page) {
            return $template->getResponse();
        }

        Controller::redirect($jf->getUrl($page));

        /*
        $template->jf_redirect = $jf->getUrl($page);

        return $template->getResponse();
        */
    }
}
