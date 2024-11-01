<?php
/*
Blog Name: F2blog.cont
Blog Type: MySQL
Blog Version: 1.0 build 11.30
Blog URI: http://bbs.f2cont.co.cc
WordPress: 2.9.2
Description: vXor Convertor plugin for converting F2blog.cont to WordPress.
Plugin Author: yeyezai
Plugin Version: 2010.06.20
Plugin URI: http://www.yeyezai.com
Author URI: http://www.yeyezai.com
*/

$vxor_dbcheck = array(
        'table' => 'members',
        'field' => 'username'
);

$vxor_extends = array(
    array( __('Attachments Folder', VXOR), 'attach_path', 'text' ),
    array( __('Protected Posts Password', VXOR), 'postpw', 'text' ),
    array( __('Original Time Zone', VXOR), 'timezone', 'text' ),
    array( __('Page About', VXOR), 'page_about', 'check', true ),
    array( __('Page Guest', VXOR), 'page_guest', 'check', false ),
);

?>