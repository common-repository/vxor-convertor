<?php
/**
 * vXor Step: Posts
 *
 * vxor_insert_post($postarr, $wp_error) 插入日志
 */

$maxid = vxor_get_maxid('Content', 'log_ID');

$sPosts = $sdb->get_results("SELECT * FROM {$dbprefix}Content WHERE log_ID >= $start AND log_ID < $start + $dbrpp ORDER BY log_ID", ARRAY_A);

if( empty($sPosts) )
    return;

foreach ($sPosts as $sPost) {
    $postarr = array(
            'ID' => $sPost['log_ID'],
            'post_author' => vxor_get_author_id($sPost['log_Author']),
            'post_date' => pj_date($sPost['log_PostTime'], $ext_timezone),
            'post_date_gmt' => pj_date($sPost['log_PostTime']),
            'post_content' => pj_content($sPost['log_Content'], $sPost['log_ID']),
            'post_title' => $sPost['log_Title'],
            'post_status' => pj_get_post_status($sPost['log_IsShow'], $sPost['log_IsDraft'], $sPost['log_Readpw']),
            'comment_status' => ($sPost['log_DisComment'] != -1) ? "open" : "closed",
            'post_password' => !empty($sPost['log_Readpw']) ? $ext_postpw : '',
            'post_modified' => $sPost['log_Modify'] ? pj_post_modified_date($sPost['log_Modify'], $ext_timezone) : pj_date($sPost['log_PostTime'], $ext_timezone),
            'post_modified_gmt' => $sPost['log_Modify'] ? pj_post_modified_date($sPost['log_Modify']) :pj_date($sPost['log_PostTime']),
            'guid' => $ext_site_url . '/?p=' . $sPost['log_ID'],

            'post_category' => $sPost['log_CateID'],
            'post_tag' => pj_get_content_tag($sPost['log_tag']),
            'sticky' => ($sPost['log_IsTop'] == -1) ? true : false,
    );

    $post_id = vxor_insert_post($postarr, true);

    if( is_wp_error($post_id) )
        return $post_id;
}

if($post_id == $maxid) {
    vxor_update_option('step', 'last_post_id', $post_id);
}


?>