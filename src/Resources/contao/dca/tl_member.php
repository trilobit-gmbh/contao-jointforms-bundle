<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Trilobit\JointformsBundle\DataProvider\Edit;

$GLOBALS['TL_DCA']['tl_member']['palettes']['__selector__'][] = 'jf_complete';
$GLOBALS['TL_DCA']['tl_member']['subpalettes']['jf_complete'] = 'jf_complete_datim';

$GLOBALS['TL_DCA']['tl_member']['fields']['jf_data'] = [
    'flag' => 1,
    'inputType' => 'textarea',
    'eval' => ['readonly' => true, 'tl_class' => 'clr'],
    'input_field_callback' => [Edit::class, 'jsonView'],
    'sql' => 'text null',
];

$GLOBALS['TL_DCA']['tl_member']['fields']['jf_complete'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'inputType' => 'checkbox',
    'eval' => ['submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_member']['fields']['jf_complete_datim'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 6,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
    'sql' => "varchar(10) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_member']['fields']['jf_last_modified'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 6,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
    'sql' => "varchar(10) NOT NULL default ''",
];

PaletteManipulator::create()
    ->addLegend('jf_legend', 'account_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField([
        'jf_last_modified',
        'jf_data',
        'jf_complete',
    ], 'jf_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member')
;
