<?php
/**
 * vXor Step: Comments
 *
 * vxor_insert_comment($commentdata) 插入评论
 */

$maxid = vxor_get_maxid('Comment', 'comm_ID');

$sComments = $sdb->get_results("SELECT * FROM {$dbprefix}Comment WHERE comm_ID >= $start AND comm_ID < $start + $dbrpp ORDER BY comm_ID", ARRAY_A);

if( empty($sComments) )
    return;

foreach ( $sComments as $sComment ) {

    //所属日志不存在，则不转换评论
    if( ! get_post( $sComment['blog_ID'] ) )
        continue;

    $commentdata = array(
            'comment_ID' => $sComment['comm_ID'],
            'comment_post_ID' => $sComment['blog_ID'],
            'comment_author' => $sComment['comm_Author'],
            'comment_author_email' => $sComment['comm_Email'],
            'comment_author_url'   => $sComment['comm_WebSite'],
            'comment_author_IP' => $sComment['comm_PostIP'],
            'comment_date' => pj_date($sComment['comm_PostTime'], $ext_timezone),
            'comment_date_gmt' => pj_date($sComment['comm_PostTime']),
            'comment_content' => vxor_content( $sComment['comm_Content'] ),
            'user_id' => vxor_get_author_id($sComment['comm_Author']),
    );

    $comment_ID = vxor_insert_comment($commentdata);

    vxor_update_maxid('last_comment_id', $comment_ID);

}

?>