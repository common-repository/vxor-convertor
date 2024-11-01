<?php
/**
 * vXor Step: Link Class
 *
 * vxor_insert_category($catarr, $taxonomy, $wp_error = false) 插入链接分类
 */

extract(get_option('vxor_step'), EXTR_SKIP);

$sLinkCats = $sdb->get_results("SELECT * FROM {$dbprefix}LinkClass ORDER BY LinkClass_ID", ARRAY_A);

if( empty($sLinkCats) )
    return;

$sLinkCat_default = 0;

foreach($sLinkCats as $sLinkCat) {
    $catarr = array(
            'cat_ID' => $sLinkCat['LinkClass_ID'] + $last_category_id,
            'cat_name' => $sLinkCat['LinkClass_Name'],
            'cat_description' => $sLinkCat['LinkClass_Title'],
    );

    $term_id = vxor_insert_category($catarr, 'link_category', true);

    if( is_wp_error($term_id) )
        return $term_id;

    /* 更新 默认链接分类目录 为第一个链接分组 */
    if(!$sLinkCat_default)
        update_option('default_link_category',$catarr['cat_ID']);

    $sLinkCat_default++;
}

?>