<?php

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 * @link       http://github.com/trilobit-gmbh/contao-jointforms-bundle
 */

namespace Trilobit\JointformsBundle\DataProvider;

use Contao\DataContainer;

/**
 * Class Edit.
 */
class Edit
{
    public static function jsonView(DataContainer $dc): string
    {
        return '<div class="widget clr">'
            .'<h3>'.$GLOBALS['TL_LANG']['tl_member']['jf_data'][0].'</h3>'
            .'<div style="height:auto;background:#f3f3f5;padding:6px;border:1px solid #aaa" class="json-tree">'
            .'</div>'
            .'<p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_member']['jf_data'][1].'</p>'
            .'</div>'
            .'<script src="/bundles/trilobitjointforms/jsonview/dist/jsonview.js"></script>'
            .'<script>'
            .'jsonview.render('
                .'jsonview.create('.$dc->activeRecord->jf_data.'),'
                .'document.querySelector(\'.json-tree\')'
            .');'
            .'</script>'
            .'<style>.json-container{background:transparent}.json-container .line{word-break:break-all}.json-container .json-key{word-break:normal}</style>'
        ;
    }
}
