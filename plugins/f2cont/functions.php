<?php

/**
 * 判断用户角色
 *
 * @param string $role 用户名
 * @return string 用户角色
 */
function f2_get_user_role($role) {
    switch ($role) {
        case 'admin':
            return 'administrator';
            break;
        case 'author':
            return 'contributor';
            break;
        case 'editor':
            return 'editor';
            break;
        default:
            return  get_option('default_role');
            break;
    }
}

/**
 * 转换日志状态
 *
 * @param int $saveType F2日志状态
 * @return string WordPress状态
 */
function f2_get_post_status($saveType) {
    switch($saveType) {
        case 0:
            return "draft";
            break;
        case 1:
            return "publish";
            break;
        case 2:
            return "inherit";
            break;
        default:
            return "private";
            break;
    }
}

/**
 * 将日志发表时间秒数转为 年-月-日 时:分:秒
 *
 * @param int $seconds 秒数
 * @param int $timezone 时区
 * @return Date Y-m-d H:i:s
 */
function f2_date($seconds, $timezone = 0) {
    $seconds = $seconds + $timezone *3600;
    return date('Y-m-d H:i:s', $seconds);
}

/**
 * 处理日志中的下载附件
 *
 * @param string $content 日志内容
 * @param array $attach
 * @return string 处理后的日志内容
 */
function f2_content_attachment($content, $attach = array()) {

    extract($attach, EXTR_SKIP);

    if( preg_match("/<!--mfileBegin-->$ID<!--mfileEnd-->/is", $content) ){
        //会员附件：<!--mfileBegin-->ID<!--mfileEnd-->
        $member = true;
        $content = str_replace("<!--mfileBegin-->$ID<!--mfileEnd-->", "<a href=\"$guid\" title=\"$title\">$title</a>", $content);
    } else {
        //普通附件：<!--fileBegin-->ID<!--fileEnd-->
        $content = str_replace("<!--fileBegin-->$ID<!--fileEnd-->", "<a href=\"$guid\" title=\"$title\">$title</a>", $content);
    }

    $content = compact( array ('member', 'content'));
    
    return $content;
}


?>