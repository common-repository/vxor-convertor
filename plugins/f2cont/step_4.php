<?php
/**
 * vXor Step: Link Groups
 *
 * vxor_insert_category($catarr, $taxonomy, $wp_error = false) 插入链接分类
 */

extract(get_option('vxor_step'), EXTR_SKIP);

$sLinkCats = $sdb->get_results("SELECT * FROM {$dbprefix}linkgroup ORDER BY id", ARRAY_A);

if( empty($sLinkCats) )
    return;

$sLinkCat_default = 0;

foreach($sLinkCats as $sLinkCat) {
    $catarr = array(
            'cat_ID' => $sLinkCat['id'] + $last_category_id,
            'cat_name' => $sLinkCat['name'],
            'cat_description' => $sLinkCat['name'],
    );

    $term_id = vxor_insert_category($catarr, 'link_category', true);

    if( is_wp_error($term_id) )
        return $term_id;

    /* 更新 默认链接分类目录 为第一个链接分组 */
    if(!$sLinkCat_default)
        update_option('default_link_category', $$term_id);

    $sLinkCat_default++;
}

?>