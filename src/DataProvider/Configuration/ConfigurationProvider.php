<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

namespace Trilobit\JointformsBundle\DataProvider\Configuration;

use Contao\Date;
use Contao\Environment;
use Contao\FormModel;
use Contao\FrontendUser;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ConfigurationProvider
{
    public function __construct(string $environment = '')
    {
        $container = System::getContainer();

        $this->tokenChecker = $container->get('contao.security.token_checker');
        $this->rootDir = $container->getParameter('kernel.project_dir');

        $this->config = [];
        $this->initialized = false;
        $this->activeForm = null;

        global $objPage;
        $this->page = $objPage;

        if (null !== $this->tokenChecker
            && $this->tokenChecker->hasFrontendUser()
        ) {
            $this->config = $this->getConfig($environment);

            if (!$this->initialized) {
                $this->init();
            }
        }

        $this->currentForm = $this->getCurrentForm();
        $this->currentStep = $this->getCurrentStep();
    }

    public function getUrl($page, $autoItem = ''): string
    {
        $url = System::getContainer()
            ->get('contao.routing.url_generator')
            ->generate(($page->alias ?: $page->id).('' !== $autoItem ? '/'.$autoItem : ''), [])
        ;

        // Remove path from absolute URLs
        if (0 === strncmp($url, '/', 1)) {
            $url = substr($url, \strlen(Environment::get('path')) + 1);
        }

        return $url;
    }

    public static function getEnvironments()
    {
        $config = System::getContainer()->getParameter('trilobit_jointforms');

        if (empty($config)
            || !\array_key_exists('environments', $config)
        ) {
            return [];
        }

        return array_keys($config['environments']);
    }

    public function getElementByTypeAndId($type, $id): array
    {
        if (!\array_key_exists('items', $this->config)
            || !\is_array($this->config['items'])
        ) {
            return [];
        }

        foreach ($this->config['items'] as $item) {
            if ($item['type'] === $type
                && $item['id'] === $id
            ) {
                return $item;
            }
        }

        return [];
    }

    public function isElementVisible($expression): bool
    {
        if (null === $this->tokenChecker
            || !$this->tokenChecker->hasFrontendUser()
        ) {
            return true;
        }

        if (empty($expression)) {
            return true;
        }

        $config['member'] = FrontendUser::getInstance();

        $json = (!empty($config['member']->jf_data)
            ? html_entity_decode($config['member']->jf_data)
            : ''
        );

        if (!empty($json)) {
            try {
                $config['jointforms'] = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
            } catch (\Exception $e) {
                $config['jointforms'] = new \stdClass();
            }
        }

        $config['app'] = new \stdClass();
        $config['app']->tools = new App();
        $config['app']->date = Date::parse('Y-m-d');
        $config['app']->time = Date::parse('H:i');
        $config['app']->tstamp = time();

        $config['app']->jf_last_modified = $config['member']->jf_last_modified;
        $config['app']->jf_complete = $config['member']->jf_complete;
        $config['app']->jf_complete_datim = $config['member']->jf_complete_datim;

        $expression = str_replace('&#39;', '\'', $expression);

        return (bool) $this->evaluateExpression(!empty($expression)
            ? html_entity_decode($expression)
            : '', $config);
    }

    /**
     * @throws \JsonException
     */
    protected function init(): void
    {
        $this->expression = new ExpressionLanguage();

        if (!\array_key_exists('items', $this->config)
            || !\is_array($this->config['items'])
        ) {
            return;
        }

        if (!\array_key_exists('member', $this->config)) {
            $this->config['member'] = FrontendUser::getInstance();
            $this->config['jointforms'] = new \stdClass();

            if (!empty($this->config['member']->jf_data)) {
                try {
                    $this->config['jointforms'] = json_decode($this->config['member']->jf_data, false, 512, \JSON_THROW_ON_ERROR);
                } catch (\Exception $e) {
                    $this->config['jointforms'] = new \stdClass();
                }
            }
        }

        $this->config['jointforms']->time = time();

        $currentItems = [];
        $newItems = [];
        $visibleForms = [];
        $visibleExpressions = [];

        foreach ($this->config['items'] as $item) {
            $item = $this->initItem($item);

            switch ($item['type']) {
                case 'tl_page':
                    $item = $this->preparePageItem($item);
                    break;

                case 'tl_form':
                    if (empty($this->config['jointforms']->{'form'.$item['id']})) {
                        $this->config['jointforms']->{'form'.$item['id']} = new \stdClass();
                    }

                    if (empty($this->config['jointforms']->{'form'.$item['id']}->jointforms_complete)) {
                        $this->config['jointforms']->{'form'.$item['id']}->jointforms_complete = false;
                    }

                    if (empty($this->config['jointforms']->{'form'.$item['id']}->jointforms_complete_datim)) {
                        $this->config['jointforms']->{'form'.$item['id']}->jointforms_complete_datim = null;
                    }

                    $item = $this->prepareFormItem($item);

                    if (!empty($item['visible'])) {
                        $visibleForms[] = $item['id'];
                    }
                    break;

                default:
                    break;
            }

            $currentItems[] = $this->finalizeItem($item);
        }

        foreach ($visibleForms as $id) {
            $visibleExpressions[] = 'jointforms.form'.$id.' && jointforms.form'.$id.'.jointforms_complete';
        }

        $expression = implode(' && ', $visibleExpressions);

        foreach ($currentItems as $item) {
            if (\array_key_exists('submit', $item)) {
                $item['visible_expression'] = $expression;
                $item['visible'] = $this->evaluateExpression($expression, $item);
            }

            if (null === $item['visible']
                && !empty($item['visible_expression'])
            ) {
                $item['visible'] = $this->evaluateExpression($item['visible_expression'], $item);
            }

            $newItems[] = $item;
        }

        $this->config['items'] = $newItems;

        $this->currentForm = $this->getCurrentForm();
        $this->currentStep = $this->getCurrentStep();

        if ($this->config['defaultPageIds']['tl_form']
            && $this->config['defaultPageIds']['tl_form'] === (int) $this->page->id
        ) {
            foreach ($this->config['items'] as &$item) {
                if ('tl_form' === $item['type']
                    && $this->currentForm === $item['id']
                ) {
                    if (\array_key_exists('class', $item)) {
                        $item['class'] .= ' active';
                    }
                    break;
                }
            }
            unset($item);
        }

        $model = FormModel::findByIdOrAlias($this->currentForm);

        $this->page->jf_title = $model->jf_title ?: $model->title;

        $this->initialized = true;
    }

    public function getConfig(string $environment = ''): array
    {
        $config = System::getContainer()->getParameter('trilobit_jointforms');

        if (empty($config)) {
            return [];
        }

        if (empty($environment)
            && \array_key_exists('environments', $config)
        ) {
            global $objPage;
            foreach ($config['environments'] as $key => $value) {
                if ($objPage->id === $value['defaultPageIds']['tl_form']) {
                    $environment = $key;
                    break;
                }
            }
        }

        if (!\array_key_exists('environments', $config)
            || !\array_key_exists($environment, $config['environments'])
        ) {
            return [];
        }

        return $config['environments'][$environment];
    }

    protected function getExpressionVars($item): array
    {
        $vars = [];

        if (!empty($item)) {
            $vars['element'] = $item;
        }

        if (!empty($this->config)) {
            $vars['config'] = $this->config;
        }

        if (!empty($this->config['jointforms'])) {
            $vars['jointforms'] = $this->config['jointforms'];
        }

        if (!empty($this->config['member'])) {
            $vars['member'] = $this->config['member'];
        }

        if (!empty($item['app'])) {
            $vars['app'] = $item['app'];
        }

        return $vars;
    }

    protected function getCurrentForm($tl_form = null): ?int
    {
        if (!\array_key_exists('items', $this->config)
            || !\is_array($this->config['items'])
        ) {
            return null;
        }

        if (null === $tl_form) {
            $tl_form = Input::get('tl_form');
            if (null === $tl_form) {
                $tl_form = Input::get('auto_item');
            }
        }

        if (null !== $tl_form
            && 'next' !== $tl_form
        ) {
            if (null !== ($model = FormModel::findByIdOrAlias($tl_form))) {
                return (int) $model->id;
            }
        }

        // open form issues?
        foreach ($this->config['items'] as $item) {
            if ('tl_form' === $item['type']) {
                try {
                    $check = json_decode($this->config['member']->jf_data ?? '', false, 512, \JSON_THROW_ON_ERROR)->{'form'.$item['id']}->jointforms_complete;
                } catch (\Exception $exception) {
                    $check = false;
                }

                if ($check) {
                    continue;
                }

                if ('todo' === $item['state']) {
                    Input::setGet('tl_form', $item['id']);

                    return $item['id'];
                }
            }
        }

        // all done? first visible form
        foreach ($this->config['items'] as $item) {
            if ('tl_form' === $item['type']
                && !empty($item['visible'])
            ) {
                Input::setGet('tl_form', $item['id']);

                return $item['id'];
            }
        }

        return null;
    }

    protected function getNextForm(): ?int
    {
        return $this->getCurrentForm('next');
    }

    protected function getCurrentStep(): ?int
    {
        if (!\array_key_exists('items', $this->config)) {
            return null;
        }

        $step = 0;

        foreach ($this->config['items'] as $item) {
            if ('tl_form' === $item['type']) {
                ++$step;

                if ($this->currentForm === $item['id']) {
                    return $step;
                }
            }
        }

        return null;
    }

    protected function getFormValue($field)
    {
        if (empty($field->name)) {
            return $field->value;
        }

        if (empty($field->pid)
            || !$this->isInJointforms($field->pid)
        ) {
            return $field->value;
        }

        $expression = 'jointforms.form'.$field->pid.' && jointforms.form'.$field->pid.'.'.$field->name.' ? jointforms.form'.$field->pid.'.'.$field->name.' : \'\'';

        return !empty($item = $this->evaluateExpression($expression, [])) ? $item : $field->value;
    }

    protected function evaluateExpression($expression, $item)
    {
        if (1 == 2 && 71 === $item['id']) {
            var_dump([
                $item,
                $expression,
                $this->getExpressionVars($item)['jointforms'],
                $this->expression->evaluate(
                    $expression,
                    $this->getExpressionVars($item)
                ),
            ]);
        }

        try {
            return $this->expression->evaluate(
                $expression,
                $this->getExpressionVars($item)
            );
        } catch (\Exception $exception) {
            if (preg_match('/^.*\?.*?\'(.*?)\'.*?\:.*?\'(.*?)\'$/', $expression, $matches)
                && !empty(trim($matches[2]))
            ) {
                return trim($matches[2]);
            }
        }
    }

    protected function initItem($item): array
    {
        if (!\array_key_exists('type', $item)) {
            $item['type'] = 'tl_form';
        }

        if (!\array_key_exists('visible', $item)
            && !\array_key_exists('visible_expression', $item)
            && !\array_key_exists('submit', $item)
        ) {
            $item['visible'] = true;
        }

        return $item;
    }

    protected function preparePageItem(array $item): array
    {
        if (!\array_key_exists('pagemodel', $item)) {
            $item['pagemodel'] = PageModel::findByPk($item['id']);
        }

        if (!\array_key_exists('model', $item)) {
            $item['model'] = PageModel::findByPk($item['id']);
        }

        if (!\array_key_exists('state', $item)) {
            $item['state'] = 'info';
        }

        if (!\array_key_exists('class', $item)) {
            $item['class'] = '';
        }

        $item['class'] .= ' '.$item['state'];

        if ($this->page->id === $item['model']->id) {
            $item['class'] .= ' active page';
        }

        $item['class'] = trim($item['class']);

        return $item;
    }

    protected function prepareFormItem($item): array
    {
        if (!\array_key_exists('pagemodel', $item)) {
            $item['pagemodel'] = PageModel::findByPk($this->config['defaultPageIds'][$item['type']] ?: $this->page->id);
        }

        if (!\array_key_exists('model', $item)) {
            $item['model'] = FormModel::findByPk($item['id']);
        }

        if (!\array_key_exists('alias', $item)) {
            $item['alias'] = $item['model']->alias;
        }

        if (!\array_key_exists('state_expression', $item)) {
            $item['state_expression'] = 'jointforms.form'.$item['id'].".jointforms_complete ? 'complete' : 'todo'";
        }

        if (!\array_key_exists('state', $item)) {
            $item['state'] = $this->evaluateExpression($item['state_expression'], $item);
        }

        if (!\array_key_exists('class', $item)) {
            $item['class'] = '';
        }

        $item['class'] .= ' '.$item['state'];

        if (!empty($this->currentForm)
            && $item['id'] === $this->currentForm
        ) {
            $item['class'] .= ' active form';
        }

        $item['class'] = trim($item['class']);

        return $item;
    }

    protected function finalizeItem($item): array
    {
        if (!\array_key_exists('title', $item)) {
            $item['title'] = $item['model']->jf_title ?: $item['model']->pageTitle ?: $item['model']->title;
        }

        if (!\array_key_exists('link', $item)) {
            if ('tl_form' === $item['type']) {
                $item['link'] = $this->getUrl($item['pagemodel'], !empty($item['alias']) ? $item['alias'] : $item['id']);
            } else {
                $item['link'] = $this->getUrl($item['pagemodel']);
            }
        }

        if (!\array_key_exists('title_expression', $item)) {
            $item['title_expression'] = 'pagemodel.title';
        }

        foreach ($item as $key => $value) {
            $matches = [];

            if (preg_match('/^(.*)_expression$/', $key, $matches)) {
                if (!\array_key_exists($matches[1], $item)) {
                    try {
                        $item[$matches[1]] = $this->evaluateExpression($value, $item);
                    } catch (\Exception $e) {
                        $item[$matches[1]] = '';
                    }
                }
            }
        }

        return $item;
    }

    protected function isInJointforms($id): bool
    {
        if (!\array_key_exists('items', $this->config)) {
            return false;
        }

        foreach ($this->config['items'] as $element) {
            if ($element['id'] === (int) $id
                && 'tl_form' === $element['type']
            ) {
                return true;
            }
        }

        return false;
    }
}

class App
{
    public function dateDiff($dateA, $dateB, $format = 'days')
    {
        if (empty($dateA) || empty($dateB)) {
            return 0;
        }

        if (is_numeric($dateA)) {
            $dateA = Date::parse('Y-m-d', $dateA);
        }

        if (is_numeric($dateB)) {
            $dateB = Date::parse('Y-m-d', $dateB);
        }

        $datimA = date_create($dateA);
        $datimB = date_create($dateB);

        $diff = date_diff($datimA, $datimB);

        return $diff->{$format};
    }
}
