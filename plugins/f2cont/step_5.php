<?php
/**
 * vXor Step: Links
 *
 * vxor_insert_link($linkdata, $wp_error) 插入链接
 */

extract(get_option('vxor_step'), EXTR_SKIP);

$sLinks = $sdb->get_results("SELECT * FROM {$dbprefix}links ORDER BY id", ARRAY_A);

if( empty($sLinks) )
    return;

foreach( $sLinks as $sLink) {
    $linkdata = array(
            'link_id' => $sLink['id'],
            'link_name' => $sLink['name'],
            'link_url' => $sLink['blogUrl'],
            'link_image' => $sLink['blogLogo'],
            'link_visible' => $sLink['isSidebar'] ? 'Y' : 'N',
            'link_category' => array($sLink['lnkGrpId'] + $last_category_id),
    );

    $link_id = vxor_insert_link($linkdata, true);

    if( is_wp_error($link_id) )
        return $link_id;

}

?>