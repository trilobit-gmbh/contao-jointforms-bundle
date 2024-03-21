<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Trilobit\JointformsBundle\DataProvider\Configuration\ConfigurationProvider;

$GLOBALS['TL_DCA']['tl_form']['fields']['jf_title'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(512) NOT NULL default ''",
    'eval' => ['tl_class' => 'clr w50'],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['jf_onDocumentReady'] = [
    'exclude' => true,
    'search' => true,
    'sorting' => true,
    'flag' => 1,
    'inputType' => 'text',
    'sql' => "varchar(512) NOT NULL default ''",
    'eval' => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['jf_environment'] = [
    'exclude' => true,
    'filter' => true,
    'inputType' => 'select',
    'options' => ConfigurationProvider::getEnvironments(),
    'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'clr w50'],
    'sql' => "varchar(256) NOT NULL default ''",
];

PaletteManipulator::create()
    ->addField([
        'jf_environment',
        'jf_title',
        'jf_onDocumentReady',
    ], 'allowTags')
    ->applyToPalette('default', 'tl_form')
;
