<?php
/**
 * vXor Step: GuestBook
 *
 * vxor_insert_comment($commentdata) 插入留言
 */

//前面是否已选择添加 GuestBook 页面
if( ! $ext_page_guest )
    return;

$maxid = vxor_get_maxid('guestbook', 'id');

$sGuestbooks = $sdb->get_results("SELECT * FROM {$dbprefix}guestbook WHERE id >= $start AND id < $start + $dbrpp ORDER BY id", ARRAY_A);

if( empty($sGuestbooks) )
    return;

foreach ( $sGuestbooks as $sGuestbook ) {

    $commentdata = array(
            'comment_ID' => $sGuestbook['id'] + $last_comment_id,
            'comment_post_ID' => $page_guest_id, // 留言簿ID
            'comment_author' => $sGuestbook['author'],
            'comment_author_email' => $sGuestbook['email'],
            'comment_author_url'   => $sGuestbook['homepage'],
            'comment_author_IP' => $sGuestbook['ip'],
            'comment_date' => f2_date($sGuestbook['postTime'], $ext_timezone),
            'comment_date_gmt' => f2_date($sGuestbook['postTime']),
            'comment_content' => vxor_content( $sGuestbook['content'] ),
            'comment_approved' => $sGuestbook['isSecret'] ? 0 : 1,
            'comment_parent' => $sGuestbook['parent'],
            'user_id' => vxor_get_author_id( $sGuestbook['author'] )
    );

    vxor_insert_comment($commentdata);

}

?>