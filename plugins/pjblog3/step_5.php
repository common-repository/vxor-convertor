<?php
/**
 * vXor Step: Links
 *
 * vxor_insert_link($linkdata, $wp_error) 插入链接
 */

extract(get_option('vxor_step'), EXTR_SKIP);

$sLinks = $sdb->get_results("SELECT * FROM {$dbprefix}Links ORDER BY link_ID", ARRAY_A);

if( empty($sLinks) )
    return;

foreach( $sLinks as $sLink) {
    $linkdata = array(
            'link_id' => $sLink['link_ID'],
            'link_name' => $sLink['link_Name'],
            'link_url' => $sLink['link_URL'],
            'link_image' => $sLink['link_Image'],
            'link_visible' => $sLink['link_IsShow'] ? 'Y' : 'N',
            'link_category' => array($sLink['Link_ClassID'] + $last_category_id),
    );

    $link_id = vxor_insert_link($linkdata, true);

    if( is_wp_error($link_id) )
        return $link_id;
}

?>