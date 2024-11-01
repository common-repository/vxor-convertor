<?php
/**
 * vXor Step: Attachments
 *
 * vxor_insert_attachment($object, $file = false) 插入附件
 */

$maxid = vxor_get_maxid('attachments', 'id');

$sAttachs = $sdb->get_results("SELECT * FROM {$dbprefix}attachments WHERE id >= $start AND id < $start + $dbrpp ORDER BY id", ARRAY_A);

if( empty($sAttachs) )
    return;

extract(get_option('vxor_step'), EXTR_SKIP);

foreach ($sAttachs as $sAttach) {
    $guid = vxor_get_attachment_guid($sAttach['name']);
    $file = vxor_get_attachment_path($sAttach['name']);

    $attachment = array(
            'ID' => $sAttach['id'] + $last_post_id,
            'post_author' => vxor_get_attachment_author_id($sAttach['logId']),
            'post_title' => $sAttach['attTitle'],

            'post_date' => f2_date($sAttach['postTime'], $ext_timezone),
            'post_date_gmt' => f2_date($sAttach['postTime']),
            'post_modified' => f2_date($sAttach['postTime'], $ext_timezone),
            'post_modified_gmt' => f2_date($sAttach['postTime']),

            'guid' => $guid,
            'post_parent' => $sAttach['logId'],
            'post_mime_type' => vxor_get_filetype($sAttach['name'], 'type'),
    );

    $metadata = array(
            'width' => $sAttach['fileWidth'],
            'height' => $sAttach['fileHeight'],
            'file' => _wp_relative_upload_path($file),
    );

    /* 所属日志不存在则跳过插入附件，否则引起重大错误 */
    if(!get_post($sAttach['logId']))
        continue;

    $attachment_id = vxor_insert_attachment($attachment, $file);
    $is_image = vxor_update_attachment_metadata($attachment_id, $file, $metadata);

    /* 处理日志中的附件，非图片附件*/
    if( !$is_image ) {
        $attch_replace = array(
                'ID' => $sAttach['id'],
                'title' => $attachment['post_title'],
                'guid' => $attachment['guid'],
        );

        $sPost = get_post($sAttach['logId'], ARRAY_A);
        if( $sPost ) {
            $sContent = f2_content_attachment($sPost['post_content'], $attch_replace);
            $wpdb->update($wpdb->posts, array('post_content'=>$sContent['content']), array('ID' => $sAttach['logId']));

            // 注册会员才能下载附件，日志类型改为 private
            if($sContent['member'])
                $wpdb->update($wpdb->posts, array('post_status'=>'private'), array('ID' => $sAttach['logId']));
        }
    }

}

if($attachment_id == $maxid) {
    vxor_update_option('step', 'last_attachment_id', $attachment_id);
}

?>