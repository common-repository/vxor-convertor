<?php
/**
 * vXor Step: Category
 *
 * vxor_insert_category($catarr, $taxonomy, $wp_error = false) 插入日志分类
 */

$sCats = $sdb->get_results("SELECT * FROM {$dbprefix}Category WHERE cate_Outlink >= 0 ORDER BY cate_ID", ARRAY_A);

if( empty($sCats) )
    return;

foreach ( $sCats as $sCat ) {
    $catarr = array(
            'cat_ID'          => $sCat['cate_ID'],
            'cat_name'        => $sCat['cate_Name'],
            'cat_description' => $sCat['cate_Intro'],
            'cat_nicename'    => $sCat['cate_Name'],
    );

    $term_id = vxor_insert_category($catarr, 'category', true);

    if( is_wp_error($term_id) )
        return $term_id;
}

vxor_update_option('step', 'last_category_id', $term_id);

?>
