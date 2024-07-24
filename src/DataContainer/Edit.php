<?php

declare(strict_types=1);

/*
 * @copyright  trilobit GmbH
 * @author     trilobit GmbH <https://github.com/trilobit-gmbh>
 * @license    LGPL-3.0-or-later
 */

namespace Trilobit\JointformsBundle\DataContainer;

use Contao\DataContainer;

/**
 * Class Edit.
 */
class Edit
{
    public static function jsonView(DataContainer $dc): string
    {
        $buffer = '<div class="widget clr">'
            .'<h3>'.$GLOBALS['TL_LANG']['tl_member']['jf_data'][0].'</h3>';

        if (!empty($dc->activeRecord->jf_data)) {
            $buffer .= '<div style="height:auto;background:#f3f3f5;padding:6px;border:1px solid #aaa" class="json-tree">'
                .'</div>'
                .'<script src="/bundles/trilobitjointforms/jsonview/dist/jsonview.js"></script>'
                .'<script>'
                    .'jsonview.render('
                    .'jsonview.create('
                        .str_replace(
                            '&#34;',
                            '"',
                            $dc->activeRecord->jf_data ?? ''
                        )
                    .'),'
                    .'document.querySelector(\'.json-tree\')'
                    .')'
                .'</script>'
                .'<style>.json-container{background:transparent}.json-container .line{word-break:break-all}.json-container .json-key{word-break:normal}</style>';
        }

        $buffer .= '<textarea name="jf_data" id="ctrl_jf_data" class="tl_textarea" rows="12" cols="80" data-action="focus->contao--scroll-offset#store" data-contao--scroll-offset-target="autoFocus">'.$dc->activeRecord->jf_data.'</textarea>'
            .'<p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_member']['jf_data'][1].'</p>'
            .'</div>';

        return $buffer;
    }
}
