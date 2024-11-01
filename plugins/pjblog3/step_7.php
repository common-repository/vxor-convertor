<?php
/**
 * vXor Step: Attachments s
 *
 * vxor_insert_attachment($object, $file = false) 插入附件
 */

$maxid = vxor_get_maxid('Files', 'id');

$sAttachs = $sdb->get_results("SELECT * FROM {$dbprefix}Files WHERE ID >= $start AND ID < $start + $dbrpp ORDER BY ID", ARRAY_A);

if( empty($sAttachs) )
    return;

extract(get_option('vxor_step'), EXTR_SKIP);

foreach ($sAttachs as $sAttach) {
    $FilesPath = str_replace('attachments/', '', $sAttach['FilesPath']);
    $guid = vxor_get_attachment_guid($FilesPath);
    $file = vxor_get_attachment_path($FilesPath);

    $attachment = array(
            'ID' => $sAttach['ID'] + $last_post_id,
            'post_title' => $FilesPath,

            'guid' => $guid,
            'post_mime_type' => vxor_get_filetype($FilesPath, 'type'),
    );

    $metadata = array(
            'file' => _wp_relative_upload_path($file),
    );

    $attachment_id = vxor_insert_attachment($attachment, $file);
    $is_image = vxor_update_attachment_metadata($attachment_id, $file, $metadata);


}

if($attachment_id == $maxid) {
    vxor_update_option('step', 'last_attachment_id', $attachment_id);
}

?>