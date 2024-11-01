<?php
/**
 * vXor Step: Posts
 *
 * vxor_insert_post($postarr, $wp_error) 插入日志
 */

$maxid = vxor_get_maxid('logs', 'id');

$sPosts = $sdb->get_results("SELECT * FROM {$dbprefix}logs WHERE id >= $start AND id < $start + $dbrpp ORDER BY id", ARRAY_A);

if( empty($sPosts) )
    return;

foreach ($sPosts as $sPost) {
    $postarr = array(
            'ID' => $sPost['id'],
            'post_author' => vxor_get_author_id($sPost['author']),
            'post_date' => f2_date($sPost['postTime'], $ext_timezone),
            'post_date_gmt' => f2_date($sPost['postTime']),
            'post_content' => vxor_content($sPost['logContent']),
            'post_title' => $sPost['logTitle'],
            'post_status' => f2_get_post_status($sPost['saveType']),
            'comment_status' => $sPost['isComment'] ? "open" : "closed",
            'ping_status' => $sPost['isTrackback'] ? "open" : "closed",
            'post_password' => $sPost['password'] ? $ext_postpw : '',
            'post_modified' => f2_date($sPost['postTime'], $ext_timezone),
            'post_modified_gmt' => f2_date($sPost['postTime']),
            'guid' => $ext_site_url . '/?p=' . $sPost['id'],

            'post_category' => $sPost['cateId'],
            'post_tag' => str_replace(';', ',', $sPost['tags']),
            'sticky' => $sPost['isTop'],
    );

    $post_id = vxor_insert_post($postarr, true);
    
    if( is_wp_error($post_id) )
        return $post_id;
}

if($post_id == $maxid) {
    vxor_update_option('step', 'last_post_id', $post_id);
}

?>