<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    proprietary
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['fields']['jf_visible_expression'] = [
        'exclude' => true,
        'search' => true,
        'sorting' => true,
        'flag' => 1,
        'inputType' => 'text',
        'sql' => "varchar(512) NOT NULL default ''",
        'eval' => [],
];

PaletteManipulator::create()
    ->addField('jf_visible_expression', 'text')
    ->applyToPalette('text', 'tl_content')
;
