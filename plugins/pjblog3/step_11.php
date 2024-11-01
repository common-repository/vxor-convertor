<?php
/**
 * vXor Step: Trackbacks
 *
 * vxor_insert_comment($commentdata) ��������
 */

$sTrackbacks = $sdb->get_results("SELECT * FROM {$dbprefix}Trackback ORDER BY tb_ID", ARRAY_A);

if( empty($sTrackbacks) )
    return;

foreach ( $sTrackbacks as $sTrackback ) {
    $commentdata = array(
            'comment_post_ID' => $sTrackback['blog_ID'],
            'comment_author' => $sTrackback['tb_Site'],
            'comment_author_url'   => $sTrackback['tb_URL'],
            'comment_date' => pj_date($sTrackback['tb_PostTime'], $ext_timezone),
            'comment_date_gmt' => pj_date($sTrackback['tb_PostTime']),
            'comment_content' => $sTrackback['tb_Intro'],
            'user_id' => 0,
    );

    wp_insert_comment($commentdata);
}

?>