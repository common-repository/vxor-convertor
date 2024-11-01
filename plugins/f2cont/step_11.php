<?php
/**
 * vXor Step: Trackbacks
 *
 * vxor_insert_comment($commentdata) ВхШыв§гУ
 */

$sTrackbacks = $sdb->get_results("SELECT * FROM {$dbprefix}trackbacks ORDER BY id", ARRAY_A);

if( empty($sTrackbacks) )
    return;

foreach ( $sTrackbacks as $sTrackback ) {
    $commentdata = array(
            'comment_post_ID' => $sTrackback['logId'],
            'comment_author' => $sTrackback['blogSite'],
            'comment_author_url'   => $sTrackback['blogUrl'],
            'comment_author_IP' => $sTrackback['ip'],
            'comment_date' => f2_date($sTrackback['postTime'], $ext_timezone),
            'comment_date_gmt' => f2_date($sTrackback['postTime']),
            'comment_content' => $sTrackback['content'],
            'user_id' => 0,
    );

    wp_insert_comment($commentdata);
}

?>