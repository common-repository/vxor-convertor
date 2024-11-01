<?php

/**
 * 判断用户角色
 *
 * @param string $role 用户名
 * @return string 用户角色
 */
function pj_get_user_role($role) {
    switch($role) {
        case 'SupAdmin':
            return 'administrator';
            break;

        default:
            return get_option('default_role');
            break;
    }
}

/**
 * 将日志发表时间 加上 时区后返回新值。
 *
 * @param date $date 日期
 * @param int $timezone 时区，默认为 0
 * @return Date Y-m-d H:i:s 加上时区后的时间值
 */
function pj_date($date, $timezone = 0) {
    $new_date = strtotime($date) + $timezone * 3600;
    $new_date = date( 'Y-m-d H:i:s', $new_date );
    return $new_date;
}

/**
 * 取得日志修改时间，并 加上 时区后返回新值。
 *
 * @param string $date 日期, 如：“[本日志由 admin 于 2010-05-27 10:18 AM 编辑]”，不包括引号。
 * @param int $timezone 时区，默认为 0
 * @return Date Y-m-d H:i:s 加上时区后的时间值
 */
function pj_post_modified_date($date, $timezone = 0) {
    preg_match('/\[(.*) (\d{4}-\d{2}-\d{2} \d{2}:\d{2}) (AM|PM) (.*)\]/is', $date, $matches);
    
    $date_new = strtotime($matches[2]);
    if( $matches[3] == 'PM' )
        $date_new += 12 * 3600;

    return date('Y-m-d H:i:s', $date_new + $timezone * 3600);
}

/**
 * 日志类型
 *
 * @param int $show -1，在前台显示；0，不显示
 * @param int $draft -1，草稿；0，非草稿
 * @param string $password 加密日志密码
 * @return string WordPress 日志类型
 */
function pj_get_post_status($show, $draft, $password) {

    if( $show == -1 && $draft == 0 || $password ) {
        return 'publish';
    } else if( $show == -1 && $draft == -1 ) {
        return 'draft';
    } else if( $show == 0 && $draft == -1 ) {
        return 'private';
    } else {
        return 'private';
    }
    
}

/**
 * 取得标签
 *
 * @param string $tags {int} 格式的标签
 * @return string 标签,标签
 */
function pj_get_content_tag($tags) {
    if( empty($tags) )
        return;

    global $sdb;
    extract( get_option('vxor_config', EXTR_SKIP) );

    $tags = str_replace(array('}{', '{', '}'), array(',', '', '' ), $tags);
    $tags_id = explode(',', $tags);
    $tags = array();
    
    foreach ($tags_id as $tag_id){
        $tag = $sdb->get_row("SELECT tag_name FROM {$dbprefix}tag WHERE `tag_id` = $tag_id", ARRAY_A);
        $tags[] = $tag['tag_name'];
    }
    $tags = implode(',', $tags);

    return $tags;
}

/**
 * 处理正文内容，并将附件所属日志 ID 保存到 vxor_step 中
 *
 * @param string $content 正文内容
 * @param int $post_id Post ID
 * @return $content 经过 vxor_content($content) 后的正文
 */
function pj_content($content, $post_id) {
    $content = vxor_content($content);

    //[down=download.asp?id=1]点击下载此文件[/down]
    if( preg_match('|\[down=download\.asp\?id=(\d+)\](.+?)\[\/down\]|is', $content) ) {
        vxor_update_option('step', 'attach_posts', $post_id, ARRAY_A );
    }

    return $content;
}

?>