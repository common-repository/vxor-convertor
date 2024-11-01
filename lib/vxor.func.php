<?php

/**
 * 将数组转换为字符
 *
 * 用于缓存
 *
 * @param $data
 * @return string
 */
function a2s($data, $returns = '') {
    static $t = 1;
    $tabType = "    ";
    $tab = str_repeat($tabType,$t);
    $data = (array)$data;
    foreach($data as $key=>$value) {
        if(is_array($value)) {
            $t++;
            $returns .= "$tab'".$key."' => array(\n".a2s($value)."$tab),\n";
        }else {
            if(!is_bool($value)) {
                $value = "'".addslashes($value)."'";
            }
            $returns .= "$tab'".$key."' => $value,\n";
        }

    }
    $returns = substr_replace($returns,'',-2,-1);
    return $returns;
}

/**
 * 取得所有包含 config.php 的插件目录名.
 *
 * vXor Convertor 插件位于 (vxor-convertor/plugins)
 *
 * @since 1.0.0
 * @version 1.1.0
 * @see get_plugin_files(), wp-admin/includes/plugin.php.
 *
 * @return array 插件文件夹下的全部插件文件夹名数组。
 */
function _vxor_get_plugins() {

    $plugin_root = VXOR_PLUGIN_DIR;
    $plugins_dir = @opendir( $plugin_root );
    $vxor_plugins = array();
    $plugins = array();

    if ( $plugins_dir ) {
        while(($file = readdir( $plugins_dir )) !== false ) {
            if ( substr($file, 0, 1) == '.' )
                continue;
            if ( is_dir( $plugin_root.'/'.$file ) ) {
                $plugins_subdir = @opendir( $plugin_root.'/'.$file );
                if ( $plugins_subdir ) {
                    while (($subfile = readdir( $plugins_subdir ) ) !== false ) {
                        if ( substr($subfile, 0, 1) == '.' )
                            continue;
                        if ( $subfile == 'config.php' ) {
                            $plugins[] = $file;
                            break;
                        }
                    }
                }
                @closedir( $plugins_subdir );
            }
        }
        @closedir( $plugins_dir );
    }

    if( !$plugins_dir || empty( $plugins ))
        return $vxor_plugins;

    foreach( $plugins as $plugin ) {
        if( !is_readable( VXOR_PLUGIN_DIR . "/$plugin/config.php" ))
            continue;

        $config = _vxor_get_plugin_config( $plugin );

        if( empty( $config['BlogName'] ) ||  empty( $config['BlogType'] ) ||!_vxor_is_dbtype( $config['BlogType'] ) )
            continue;

        $vxor_plugins[] = $plugin;
    }

    return $vxor_plugins;
}

/**
 * 从 config.php 中读取配置.
 *
 * <code>
 * /*
 * Blog Name:      必需，博客程序名称
 * Blog Type:      必需，博客数据库类型，MySQL、ACCESS、BSP(博客服务托管商)
 * Blog Version:   可选，博客程序版本，建议注明
 * Blog URI:       可选，博客程序主页
 * WordPress:      可选，要求最低版本的 WordPress。默认当前版本
 * Description:    可选，转换插件说明
 * Plugin Author:  可选，转换插件作者
 * Plugin Version: 可选，转换插件版本
 * Plugin URI:     可选，转换插件主页
 * Author URI:     可选，转换插件作者主页
 * Text Domain:    可选，插件语言名
 * Domain Path:    可选，插件语言文件路径
 * * / # 删除 * 与 / 内的空格
 * </code>
 *
 * 返回的数组中包含的内容对应上述内容：
 *      'BlogName'
 *      'BlogType'
 *      'BlogVersion'
 *      'BlogURI'
 *      'WordPress'
 *      'Description'
 *      'Author'
 *      'AuthorURI'
 *      'Version'
 *      'URI'
 *      'TextDomain'
 *      'DomainPath'
 *      'Title',        博客名 版本 to WordPress WP版本
 *      'Meta',         [转换程序版本][ | 作者为][ | 访问插件主页][ | 访问博客主页]
 *
 * @since 1.0.0
 * @see get_plugin_data(), wp-admin/includes/plugin.php.
 *
 * @param string $plugin 插件文件夹名
 * @param bool $markup 如果返回的数据中包含 HTML 代码则使用
 * @param bool $translate 如果返回的数据中需要翻译
 * @return array 见注释
 */
function _vxor_get_plugin_config( $plugin, $markup = true, $translate = true ) {

    $file = VXOR_PLUGIN_DIR . "/$plugin/config.php";

    $default_headers = array(
            'BlogName'    => 'Blog Name',
            'BlogType'    => 'Blog Type',
            'BlogVersion' => 'Blog Version',
            'BlogURI'     => 'Blog URI',
            'WordPress'   => 'WordPress',
            'Description' => 'Description',
            'Author'      => 'Plugin Author',
            'AuthorURI'   => 'Author URI',
            'Version'     => 'Plugin Version',
            'URI'         => 'Plugin URI',
            'TextDomain'  => 'Text Domain',
            'DomainPath'  => 'Domain Path'
    );
    $config = array();

    $config = get_file_data( $file, $default_headers, VXOR );

    $config['Title'] = $config['BlogName'];
    $config['Meta'] = '';

    if ( $markup || $translate )
        $config = _vxor_get_config_markup_translate( $file, $config, $markup, $translate );

    return $config;
}

/**
 * 取得转换插件文件夹内的全部转换步骤。
 *
 * @since 1.1.0
 *
 * @param string $plugin 转换插件文件夹名称
 * @return WP_Error|array 没有转换步骤则返回 WP_Error，存在转换步骤则返回 array( 'step'=> int, 'name'=>string)
 */
function _vxor_get_plugin_steps( $plugin ) {
    $plugin_root = VXOR_PLUGIN_DIR . "/$plugin";
    $plugin_dir = @opendir( $plugin_root );
    $plugin_steps = array();
    $steps = array();
    $names = array();

    if( $plugin_dir ) {
        while(($file = readdir( $plugin_dir )) !== false ) {
            if ( substr($file, 0, 1) == '.' )
                continue;
            if ( preg_match("/^step_(\d+)\.php$/is", $file, $matches) ) {
                if( $name = _vxor_get_plugin_step_name("$plugin_root/$file") ) {
                    $steps[$matches[1]] = array( 'step' => $matches[1], 'name' => $name );
                }
            }
        }
    }
    @closedir( $plugin_root );

    if( empty($steps) ) {
        return new WP_Error( 'no_steps', 'No convert steps for this plugin.' );
    }

    ksort($steps);
    $i=0;
    foreach ( $steps as $step ) {
        $i++;
        $plugin_steps['step'][$i] = $step['step'];
        $plugin_steps['name'][$i] = $step['name'];
    }

    return $plugin_steps;
}

/**
 * 取得转换步骤的 vXor Step 名称。
 *
 * @since 1.1.0
 *
 * @param string $step 转换步骤完整路径
 * @return string 转换步骤名称
 */
function _vxor_get_plugin_step_name( $step ) {
    $step_data = implode( '', file( $step ));

    $name = '';
    if ( preg_match( '|vXor Step:(.*)$|mi', $step_data, $name ) ) {
        $name = _cleanup_header_comment($name[1]);
    }

    if( !empty($name) )
        return $name;
}

/**
 *
 * @since 1.0.0
 * @see _get_plugin_data_markup_translate(), wp-admin/includes/plugin.php.
 *
 * @param string $file Path to the config.php file.
 * @param array $config Config.php headers array.
 * @param bool $markup If the returned data should have HTML markup applied.
 * @param bool $translate If the returned data should be translated.
 */
function _vxor_get_config_markup_translate( $file, $config, $markup = true, $translate = true ) {
    global $wp_version;

    //Translate fields
    if( $translate && ! empty($config['TextDomain']) ) {
        if( ! empty( $config['DomainPath'] ) )
            load_plugin_textdomain($config['TextDomain'], false, dirname($file). $config['DomainPath']);
        else
            load_plugin_textdomain($config['TextDomain'], false, dirname($file));

        foreach ( array('BlogName', 'BlogType', 'BlogVersion', 'BlogURI', 'WordPress', 'Description', 'Author', 'AuthorURI', 'Version', 'URI') as $field )
            $config[ $field ] = translate($config[ $field ], $config['TextDomain']);
    }

    //Apply Markup
    if ( $markup ) {
        $config['Title'] = sprintf( '%s %s to WordPress %s', $config['BlogName'], $config['BlogVersion'], $config['WordPress'] );

        if( empty($config['WordPress']) )
            $config['WordPress'] = $wp_version;

        $config['BlogType'] = strtolower( $config['BlogType'] );

        $config['Description'] = wptexturize( $config['Description'] );


        $meta = array();
        if( ! empty($config['Version']) )
            $meta[] = sprintf( __('Version %s'), $config['Version'] );
        if ( ! empty($config['Author']) ) {
            $author = sprintf( __('By %s'), $config['Author'] );
            if(! empty($config['AuthorURI']) ) {
                $author = '<a href="' . $config['AuthorURI'] . '" title="' . __( 'Visit author homepage' ) . '">' . $author . '</a>';
            }
            $meta[] = $author;
        }
        if( ! empty($config['URI'] )) {
            $meta[] = '<a href="' . $config['URI'] . '" title="' . __( 'Visit plugin site' ) . '">' . __('Visit plugin site') . '</a>';
        }
        if( ! empty($config['BlogURI']) && ! empty($config['BlogName']) ) {
            $meta[] = '<a href="'. $config['BlogURI'] . '" title="'. sprintf(__('Visit %s site', VXOR), $config['BlogName']) . '">'. sprintf(__('Visit %s site', VXOR), $config['BlogName']) .'</a>';
        }
        $config['Meta'] .= implode( ' | ', $meta );
    }

    $allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());

    // Sanitize all displayed data
    $config['Title']       = wp_kses($config['Title'], $allowedtags);
    $config['Version']     = wp_kses($config['Version'], $allowedtags);
    $config['Description'] = wp_kses($config['Description'], $allowedtags);
    $config['Author']      = wp_kses($config['Author'], $allowedtags);

    return $config;
}

/**
 * 检测数据库类型是否为指定类型。
 *
 * @since 1.0.0
 *
 * @param string $type 数据库类型字符串，小写。
 * @return bool 指定类型返回 true，否则返回 false.
 */
function _vxor_is_dbtype( $type ) {
    $allowtype = array( 'mysql', 'access', 'bsp');

    if( in_array($type, $allowtype) )
        return true;
}



/**
 * 输出独立博客数据库设置表格
 *
 * @since 1.0.0
 * @version 1.2.0
 */
function _vxor_blog_db_form($config) {
    if( $db = get_option('vxor_config') )
        extract($db, EXTR_SKIP);
    ?>

<table class="form-table">
    <tr>
        <th scope="row"><label for="dbuser"><?php printf(__('%s Database User', VXOR), $config['BlogName']); ?></label></th>
        <td><input type="text" name="dbuser" id="dbuser" value="<?php echo $dbuser; ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="dbpassword"><?php printf(__('%s Database Password', VXOR), $config['BlogName']); ?></label></th>
        <td><input type="password" name="dbpassword" id="dbpassword" value="<?php echo $dbpassword; ?>" /></td>
    </tr>
        <?php if( $config['BlogType'] == 'mysql' ) { ?>
    <tr>
        <th scope="row"><label for="dbname"><?php printf(__('%s Database Name', VXOR), $config['BlogName']); ?></label></th>
        <td><input type="text" id="dbname" name="dbname" value="<?php echo $dbname; ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="dbhost"><?php printf(__('%s Database Host', VXOR), $config['BlogName']); ?></label></th>
        <td><input type="text" id="dbhost" name="dbhost" value="<?php echo $dbhost ? $dbhost : 'localhost'; ?>" /></td>
    </tr>
            <?php } else if( $config['BlogType'] == 'access' ) {
            $_temps = vxor_get_files(VXOR_TEMP_DIR, 'mdb');
            ?>
    <tr>
        <th scope="row"><label for="dbhost"><?php printf(__('%s Database Host', VXOR), $config['BlogName']); ?></label></th>
        <td>
            <?php echo _vxor_select_html($_temps, 'dbhost', 'dbhost'); ?>
        </td>
    </tr>
            <?php } ?>
    <tr>
        <th scope="row"><label for="dbprefix"><?php printf(__('%s Table prefix', VXOR), $config['BlogName']); ?></label></th>
        <td><input type="text" name="dbprefix" id="dbprefix" value="<?php echo $dbprefix; ?>" /></td>
    </tr>
    <tr>
        <th scope="row"><label for="dbrpp"><?php _e('Records / Time', VXOR); ?></label></th>
        <td><input type="text" name="dbrpp" id="dbrpp" value="<?php echo $dbrpp ? $dbrpp : 100; ?>" /></td>
    </tr>
</table>

    <?php

}

/**
 * 输出独立博客扩展设置表格
 *
 * @since 1.0.0
 * @version 1.1.0
 */
function _vxor_blog_extend_form($sdb) {
    if( $extend = get_option('vxor_extend') )
        extract( $extend, EXTR_SKIP );
    $plugin = $sdb->plugin;
    ?>
<table class="form-table" id="extend-table">
        <?php _vxor_blog_extends($sdb->vxor_extends); ?>
    <tr>
        <th scope="row"><?php _e('Option Name', VXOR); ?></th>
        <td><?php _e('Old Value', VXOR); ?></td>
        <td><?php _e('New Value', VXOR); ?></td>
    </tr>
    <tr>
        <th scope="row"><label for="ext_url_old"><?php _e('Blog URL', VXOR); ?></label></th>
        <td><input type="text" name="ext_url_old" id="ext_url_old" value="<?php echo $ext_url_old; ?>" /></td>
        <td><input type="text" id="ext_url_new" name="ext_url_new" value="<?php echo $ext_url_new; ?>" /></td>
    </tr>
        <?php _vxor_blog_extend_post_replace(true); ?>
</table>
<p>
    <a href="#" id="ext_post_add" class="button"><?php _e('ADD', VXOR); ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" id="ext_post_del" class="button"><?php _e('DELETE', VXOR); ?></a>
</p>
    <?php
}

/**
 * 输出扩展设置 - 日志替换内容表格
 *
 * @since 1.0.0
 * @version 1.1.0
 *
 * @param bool $html True 为输出 HTML，false 则不输出
 * @return int 返回已设置的个数
 */
function _vxor_blog_extend_post_replace($html = false) {
    if(!$extend = get_option('vxor_extend'))
        return 0;

    if( !$html )
        return count($extend['ext_post_old']);

    $id = 0;
    $ext_post_old = $extend['ext_post_old'];
    $ext_post_new = $extend['ext_post_new'];

    if( empty( $ext_post_old) )
        return ;
    foreach ( $ext_post_old as $post_old ) {
        $id++;
        ?>
<tr id="ext_post_<?php echo $id; ?>">
    <th scope="row"><label for="ext_post_old_<?php echo $id; ?>"><?php printf(__('Post Replacement %s', VXOR), $id); ?></label></th>
    <td><input type="text" name="ext_post_old[]" id="ext_post_old_<?php echo $id; ?>" value="<?php echo $ext_post_old[$id-1]; ?>"></td>
    <td><input type="text" name="ext_post_new[]" id="ext_post_new_<?php echo $id; ?>" value="<?php echo $ext_post_new[$id-1]; ?>"></td>
</tr>
        <?php
    }
}

/**
 * 显示自定义扩展设置。
 *
 * @param array $extends config.php 中的 vxor_extends 变量
 * @return NULL $extends 为空则返回空。
 */
function _vxor_blog_extends($extends) {
    if( empty($extends) )
        return;

    if( $opt_extend = get_option('vxor_extend') )
        extract( $opt_extend, EXTR_SKIP );

    foreach ( $extends as $extend ) {
        $name = $extend['name'];
        $ID = $extend['ID'];
        $type = $extend['type'];
        $value = $extend['value'];
        $ext = 'ext_' . $ID;
        ?>
<tr>
    <th scope="row"><label for="<?php echo 'ext_'.$ID;?>"><?php echo $name;?></label></th>
    <td>
                <?php
                if( $type == 'text' ) {
                    echo '<input type="text" name="ext_'. $ID .'" id="ext_'. $ID .'" value="'. $$ext .'" />' ."\n";
                } elseif( $type == 'check' ) {
                    if( $value )
                        $check = ' checked="checked"';
                    else
                        $check = $$ext ? ' checked="checked"' : '';
                    echo '<label for="ext_'. $ID .'"><input type="checkbox" name="ext_'. $ID .'" id="ext_'. $ID .'"'. $check .' />&nbsp;' . __('Add') . "</label>\n";
                } elseif( $type == 'select' ) {
                    echo 'select';
                }
                ?>
    </td>
    <td></td>
</tr>
        <?php
    }
}

/**
 * 载入转换函数文件
 *
 * @since 1.0.0
 */
function _vxor_load_functions($plugin) {
    // 博客转换所需函数
    if(file_exists(VXOR_PLUGIN_DIR . "/$plugin/functions.php"))
        require_once VXOR_PLUGIN_DIR . "/$plugin/functions.php";

    // 博客转换所需函数，扩展用
    if(file_exists(VXOR_PLUGIN_DIR . "/$plugin/extend_functions.php"))
        require_once VXOR_PLUGIN_DIR . "/$plugin/extend_functions.php";
}

/**
 * 取得上次 action，并写入当前 action
 *
 * @since 1.0.0
 *
 * @param string $thisaction 当前 action
 * @return string 上次 action
 */
function _vxor_get_last_action($thisaction) {
    $last_action = get_option('vxor_action');
    update_option('vxor_action', $thisaction);

    return $last_action;
}

/**
 * 初始化数据库
 *
 * @since 1.0.0
 *
 * @global Class $wpdb wpdb class
 * @param $init_user True 为创建临时用户表，false 则不创建。默认 false
 */
function _vxor_db_init($init_user = false) {
    global $wpdb, $table_prefix;

    printf(__('Initialing database...', VXOR));

    $options = array('action', 'stats', 'step');
    foreach ( $options as $option ) {
        $option = 'vxor_' . $option;
        update_option( $option , array());
    }
    update_option('sticky_posts', array());

    $tables = array( 'commentmeta', 'comments', 'links', 'postmeta', 'posts',
            'terms', 'term_relationships', 'term_taxonomy', 'users', 'usermeta' );
    foreach ( $tables as $table ) {
        if($table == 'users' || $table == 'usermeta')
            $table = $wpdb->$table.'_tmp';
        else
            $table = $wpdb->$table;

        $wpdb->query("TRUNCATE TABLE $table");
    }

    if($init_user) {
        if ( ! empty($wpdb->charset) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if ( ! empty($wpdb->collate) )
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->users}_tmp (
ID bigint(20) unsigned NOT NULL auto_increment,
user_login varchar(60) NOT NULL default '',
user_pass varchar(64) NOT NULL default '',
user_nicename varchar(50) NOT NULL default '',
user_email varchar(100) NOT NULL default '',
user_url varchar(100) NOT NULL default '',
user_registered datetime NOT NULL default '0000-00-00 00:00:00',
user_activation_key varchar(60) NOT NULL default '',
user_status int(11) NOT NULL default '0',
display_name varchar(250) NOT NULL default '',
PRIMARY KEY  (ID),
KEY user_login_key (user_login),
KEY user_nicename (user_nicename)
) $charset_collate;";
        $wpdb->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->usermeta}_tmp (
umeta_id bigint(20) unsigned NOT NULL auto_increment,
user_id bigint(20) unsigned NOT NULL default '0',
meta_key varchar(255) default NULL,
meta_value longtext,
PRIMARY KEY  (umeta_id),
KEY user_id (user_id),
KEY meta_key (meta_key)
) $charset_collate;";
        $wpdb->query($sql);
    }

}

/**
 * 删除 $wpdb->users, $wpdb->usermeta
 * 重命名 $wpdb->users_tmp, $wpdb->usermeta_tmp 为上者
 *
 * @since 1.0.0
 * @version 1.2.0
 *
 * @global class $wpdb
 * @param bool $del_users 是否删除用户表
 */
function _vxor_db_del($del_user) {
    if( !$del_user )
        return;

    global $wpdb;

    if($wpdb->query("SHOW TABLES LIKE '{$wpdb->users}_tmp'")) {
        $wpdb->query("DROP TABLE $wpdb->users");
        $wpdb->query("RENAME TABLE {$wpdb->users}_tmp TO $wpdb->users");
    }
    if($wpdb->query("SHOW TABLES LIKE '{$wpdb->usermeta}_tmp'")) {
        $wpdb->query("DROP TABLE $wpdb->usermeta");
        $wpdb->query("RENAME TABLE {$wpdb->usermeta}_tmp TO $wpdb->usermeta");
    }
}

/**
 * 生成 select 元素代码
 *
 * @since 1.2.0
 *
 * @param array $array 选项数组
 * @param mixed $id    id
 * @param mixed $name  name
 * @return string      select 元素代码
 */
function _vxor_select_html($array, $id, $name) {
    $html = '<select id="'. $id .'" name="'. $name .'">'."\n";

    foreach ($array as $value) {
        $checked = '';
        if( $id == $value )
            $checked = '" selected="selected';
        $html .= '<option value="'. $value . $checked .'">'. $value .'</option>'."\n";
    }

    $html .= '</select>'."\n";
    
    return $html;
}

?>
