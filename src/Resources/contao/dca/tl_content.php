<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

$GLOBALS['TL_DCA']['tl_content']['fields']['add_jf_logic'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['jf_visible_expression'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(512) NOT NULL default ''",
    'eval' => ['tl_class' => 'clr'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['jf_environment'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'select',
    'options' => ConfigurationProvider::getEnvironments(),
    'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(256) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['jf_jumpTo'] = [
    'exclude' => true,
    'inputType' => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval' => ['mandatory' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'add_jf_logic';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['add_jf_logic'] = 'jf_environment,jf_visible_expression';

$GLOBALS['TL_DCA']['tl_content']['palettes']['jf_summary'] = '{type_legend},type,headline;{source_legend},jf_environment,jf_visible_expression;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['jf_redirect'] = '{type_legend},type,headline;{source_legend},jf_environment,jf_visible_expression,jf_jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['jf_navigation'] = '{type_legend},type,headline;{source_legend},jf_environment,jf_visible_expression;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['jf_form'] = '{type_legend},type,headline;{source_legend},jf_environment,jf_visible_expression;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';

PaletteManipulator::create()
    ->addLegend('jf_legend', 'template_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField([
        'add_jf_logic',
    ], 'jf_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('headline', 'tl_content')
    ->applyToPalette('text', 'tl_content')
    ->applyToPalette('html', 'tl_content')
    ->applyToPalette('list', 'tl_content')
    ->applyToPalette('table', 'tl_content')
    ->applyToPalette('accordionSingle', 'tl_content')
    ->applyToPalette('code', 'tl_content')
    ->applyToPalette('markdown', 'tl_content')
    ->applyToPalette('hyperlink', 'tl_content')
    ->applyToPalette('toplink', 'tl_content')
    ->applyToPalette('image', 'tl_content')
    ->applyToPalette('gallery', 'tl_content')
    ->applyToPalette('player', 'tl_content')
    ->applyToPalette('youtube', 'tl_content')
    ->applyToPalette('vimeo', 'tl_content')
    ->applyToPalette('download', 'tl_content')
    ->applyToPalette('downloads', 'tl_content')
    ->applyToPalette('alias', 'tl_content')
    ->applyToPalette('article', 'tl_content')
    ->applyToPalette('teaser', 'tl_content')
    ->applyToPalette('form', 'tl_content')
    ->applyToPalette('module', 'tl_content')
;
