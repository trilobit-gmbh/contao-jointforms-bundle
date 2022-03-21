<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mandatory']['eval']['tl_class'] = 'w50';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['jf_visible_expression'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['jf_short_label'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(512) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['label']['eval']['tl_class'] = 'clr w50';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['jf_hint'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['tl_class' => 'long'],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['jf_onChange'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(255) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

PaletteManipulator::create()
    ->addField([
        'jf_short_label',
    ], 'type_legend', PaletteManipulator::POSITION_APPEND)
    ->addLegend('jf_legend', 'template_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField([
        'jf_hint',
        'jf_visible_expression',
        'jf_onChange',
    ], 'jf_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('text', 'tl_form_field')
    ->applyToPalette('textdigit', 'tl_form_field')
    ->applyToPalette('textarea', 'tl_form_field')
    ->applyToPalette('select', 'tl_form_field')
    ->applyToPalette('radio', 'tl_form_field')
    ->applyToPalette('checkbox', 'tl_form_field')
    ->applyToPalette('upload', 'tl_form_field')
    ->applyToPalette('range', 'tl_form_field')
;
