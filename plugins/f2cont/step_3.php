<?php
/**
 * vXor Step: Categories
 *
 * vxor_insert_category($catarr, $taxonomy, $wp_error = false) 插入日志分类
 */

$sCats = $sdb->get_results("SELECT * FROM {$dbprefix}categories ORDER BY id", ARRAY_A);

if( empty($sCats) )
    return;

foreach ( $sCats as $sCat ) {
    $catarr = array(
            'cat_ID'          => $sCat['id'],
            'cat_name'        => $sCat['name'],
            'cat_description' => $sCat['cateTitle'],
            'cat_nicename'    => $sCat['name'],
            'cat_parent'      => $sCat['parent'],
    );

    $term_id = vxor_insert_category($catarr, 'category', true);

    if( is_wp_error($term_id) )
        return $term_id;
}

vxor_update_option('step', 'last_category_id', $term_id);

?>