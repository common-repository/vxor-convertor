<?php
/*
Blog Name: PJBlog3
Blog Type: ACCESS
Blog Version: 3.2.8.352
Blog URI: http://www.pjhome.net
WordPress: 2.9.2
Description: vXor Convertor plugin for converting PJBlog to WordPress.
Plugin Author: yeyezai
Plugin Version: 2010.06.20
Plugin URI: http://www.yeyezai.com
Author URI: http://www.yeyezai.com
*/

$vxor_dbcheck = array(
        'table'=>'Member',
        'field' => 'mem_Name'
);

$vxor_extends = array(
    array( __('Attachments Folder',VXOR), 'attach_path', 'text', $plugin ),
    array( __('Protected Posts Password', VXOR), 'postpw', 'text' ),
    array( __('Original Time Zone', VXOR), 'timezone', 'text' ),
    array( __('Page About', VXOR), 'page_about', 'check', true ),
    array( __('Administator Name', VXOR), 'admin_name', 'text' ),
    array( __('Administator Password', VXOR), 'admin_pass', 'text' ),
);

?>