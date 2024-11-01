<?php
/**
 * vXor Step: Comments
 *
 * vxor_insert_comment($commentdata) 插入评论
 */

$maxid = vxor_get_maxid('comments', 'id');

$sComments = $sdb->get_results("SELECT * FROM {$dbprefix}comments WHERE id >= $start AND id < $start + $dbrpp ORDER BY id", ARRAY_A);

if( empty($sComments) )
    return;

foreach ( $sComments as $sComment ) {

    //所属日志不存在，则不转换评论
    if( ! get_post( $sComment['logId'] ) )
        continue;

    $commentdata = array(
            'comment_ID' => $sComment['id'],
            'comment_post_ID' => $sComment['logId'],
            'comment_author' => $sComment['author'],
            'comment_author_email' => $sComment['email'],
            'comment_author_url'   => $sComment['homepage'],
            'comment_author_IP' => $sComment['ip'],
            'comment_date' => f2_date($sComment['postTime'], $ext_timezone),
            'comment_date_gmt' => f2_date($sComment['postTime']),
            'comment_content' => vxor_content( $sComment['content'] ),
            'comment_approved' => $sComment['isSecret'] ? 0 : 1,
            'comment_parent' => $sComment['parent'],
            'user_id' => vxor_get_author_id($sComment['author']),
    );

    $comment_ID = vxor_insert_comment($commentdata);

    vxor_update_maxid('last_comment_id', $comment_ID);

}

?>