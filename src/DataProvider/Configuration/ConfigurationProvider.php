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

        if (null !== $this->tokenChecker && $this->tokenChecker->hasFrontendUser()) {
            $this->config = $this->getConfig($environment);

            if (!$this->initialized) {
                $this->init();
            }
        }

        $this->currentForm = $this->getCurrentForm('__construct');
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
        if (null === $this->tokenChecker || !$this->tokenChecker->hasFrontendUser()) {
            return true;
        }

        if (empty($expression)) {
            return true;
        }

        $config['member'] = FrontendUser::getInstance();

        $json = html_entity_decode($config['member']->jf_data);

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
        $expression = str_replace('&#39;', '\'', $expression);

        return (bool) $this->evaluateExpression(html_entity_decode($expression), $config);
    }

    /**
     * @throws \JsonException
     */
    protected function init(): void
    {
        $this->expression = new ExpressionLanguage();

        if (!\is_array($this->config['items'])) {
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
            if ($item['submit']) {
                $item['visible_expression'] = $expression;
                $item['visible'] = $this->evaluateExpression($expression, $item);
            }

            $newItems[] = $item;
        }

        $this->config['items'] = $newItems;

        $this->currentForm = $this->getCurrentForm();

        if ($this->config['defaultPageIds']['tl_form']
            && $this->config['defaultPageIds']['tl_form'] === (int) $this->page->id
        ) {
            foreach ($this->config['items'] as &$item) {
                if ('tl_form' === $item['type']
                    && $this->currentForm === $item['id']
                ) {
                    $item['class'] .= ' active';
                    break;
                }
            }
            unset($item);
        }

        $this->initialized = true;
    }

    protected function getConfig(string $environment = ''): array
    {
        $config = System::getContainer()->getParameter('trilobit_jointforms');

        if (empty($config)
            || !\array_key_exists('environments', $config)
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

    protected function getCurrentForm(): ?int
    {
        if (!\is_array($this->config['items'])) {
            return null;
        }

        $tl_form = Input::get('tl_form');

        if ('next' === $tl_form) {
            $tl_form = null;
        }

        if (null !== $tl_form) {
            if (null !== ($model = FormModel::findById($tl_form))) {
                return (int) $model->id;
            }

            if (null !== ($model = FormModel::findByAlias($tl_form))) {
                return (int) $model->id;
            }
        }

        // open form issues?
        foreach ($this->config['items'] as $item) {
            if ('tl_form' === $item['type']
                && 'todo' === $item['state']
                && !empty($item['visible'])
            ) {
                Input::setGet('tl_form', $item['id']);

                return $item['id'];
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
        return $this->expression->evaluate(
            $expression,
            $this->getExpressionVars($item)
        );
    }

    protected function initItem($item): array
    {
        if (!\array_key_exists('type', $item)) {
            $item['type'] = 'tl_form';
        }

        if (!\array_key_exists('visible', $item)
            && !\array_key_exists('visible_expression', $item)
            && !$item['submit']
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
            try {
                $item['state'] = $this->evaluateExpression($item['state_expression'], $item);
            } catch (\Exception $e) {
                $item['state'] = 'todo';
            }
        }

        $item['class'] .= ' '.$item['state'];

        if ($item['id'] === $this->currentForm) {
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
                $item['link'] = $this->getUrl($item['pagemodel'], $item['type'].'/'.(!empty($item['alias']) ? $item['alias'] : $item['id']));
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
                        //var_dump($element[$key].': '.$e->getMessage().' (expression='.$element[$key].')');
                        $item[$matches[1]] = '';
                    }
                }
            }
        }

        return $item;
    }

    protected function isInJointforms($id): bool
    {
        foreach ($this->config['items'] as $element) {
            if ($element['id'] === (int) $id
                && 'tl_form' === $element['type']
            ) {
                return true;
            }
        }

        return false;
    }

    /*
     * todo: check usage
     */
    protected function isFormFieldVisible($formField): bool
    {
        $id = $formField->pid;
        $expression = $formField->jf_visible_expression;

        if (empty($id) || !$this->isInJointforms($id)) {
            return true;
        }

        if ('' === $expression) {
            return true;
        }

        return (bool) $this->evaluateExpression(html_entity_decode($expression), []);
    }
}

class App
{
    public function dateDiff($dateA, $dateB, $format = 'days')
    {
        if (empty($dateA) || empty($dateB)) {
            return 0;
        }

        $datimA = date_create($dateA);
        $datimB = date_create($dateB);

        $diff = date_diff($datimA, $datimB);

        return $diff->{$format};
    }
}
