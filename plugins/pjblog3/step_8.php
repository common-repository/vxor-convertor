<?php
/**
 * vXor Step: Posts with Attachments
 *
 * 修改带附件的日志，将附件改为下载地址，并修正附件在数据库中的部分值。
 */

if( !$maxid ) {
    $vxor_step = get_option( 'vxor_step' );
    $attach_posts = $vxor_step['attach_posts'];
    $maxid = count($attach_posts);
}

for( $_i = $start; $_i <= $maxid || $_i < $start + $dbrpp; $_i++ ) {
    $post_id = $attach_posts[$_i-1];
    $post = get_post($post_id, ARRAY_A);
    preg_match_all('|\[down=download\.asp\?id=(\d+)\](.+?)\[\/down\]|is', $post['post_content'], $matches);
    $content = $post['post_content'];

    foreach( $matches[1] as $key => $attach_id ) {
        $attach_id_new = $attach_id + $last_post_id;
        $attach = get_post($attach_id_new, ARRAY_A);

        /* 不存在该附件则不修改 Post 的内容 */
        if(!$attach)
            continue;
        
        $attach_guid_html = '<a href="'.$attach['guid'].'">'. $matches[2][$key] .'</a>';
        $content = str_replace($matches[0][$key], $attach_guid_html, $content);

        $wpdb->update($wpdb->posts, array('post_author' => $post['post_author'], 'post_parent' => $post_id), array('ID' => $attach_id_new));
        $wpdb->update($wpdb->posts, array('post_content' => $content), array('ID' => $post_id));
    }
}

?>
