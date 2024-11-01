<?php
/*
Plugin Name: vXor Convertor
Plugin URI: http://www.yeyezai.com/
Description: Convert multiple blogs to WordPress.
Author: yeyezai
Version: 1.2.0
Author URI: http://www.yeyezai.com/
License: GPL v3 - http://www.gnu.org/licenses/gpl.html
*/

require_once dirname(__FILE__) . '/lib/vxor.class.php';

$vXorConvertor = new vXorConvertor();
$vXorConvertor->dispatch();

?>
