<?php

/**
 * 检测是否为 WP_Error 对象。
 *
 * @since 1.0.0
 *
 * @param mixed $thing 检测未知的变量是否为 WordPress 错误对象
 * @return bool 如果不是 is_wp_error 返回 true，是则返回 false
 */
function vxor_is_error( $thing ) {
    if( !is_wp_error($thing) )
        return true;

    $message = '<p>';
    $message .= __('Error Messgae: ', VXOR) . $thing->get_error_message();
    if($thing->get_error_data()) {
        $message .= '<br>' . __('Error Data: ', VXOR) . '<strong>' . $thing->get_error_data() .'</strong>';
    }
    $message .= '</p>';

    echo $message;
    return false;
}

/**
 * 读取指定文件夹下的指定类型文件
 *
 * @since 1.0.0
 *
 * @param string $path 指定文件夹完整路径
 * @param string $exp 扩展名，指定类型
 * @return array $exp为空则返回全部文件数组，否则返回指定类型文件数组
 */
function vxor_get_files( $path, $exp = '' ) {
    $_temp_dir = @opendir( $path );
    $_temp_files = array();
    $exps = explode('|', $exp);


    if( $_temp_dir ) {
        while(($file = readdir( $_temp_dir )) !== false ) {
            if ( substr($file, 0, 1) == '.' || is_dir( $path.'/'.$file ) )
                continue;

            if( !empty($exps) ) {
                $type = vxor_get_filetype("$path/$file", 'ext');
                if( in_array( $type, $exps ) )
                    $_temp_files[] = $file;
            } else {
                $_temp_files[] = $file;
            }

        }
    }
    @closedir( $path );

    return $_temp_files;
}

/**
 * upload_mimes Filter
 *
 * @since 1.0.0
 *
 * @param string $filename File name or path.
 * @param string $key 返回的键值。ext，扩展名；type，文件类型
 * @return array File type
 */
function vxor_get_filetype( $filename, $key = 'ext') {
    $file = wp_check_filetype($filename);
    return $file[$key];
}

/**
 * 更新 option
 *
 * @since 1.0.0
 *
 * @param string $field 字段名称，自动在前添加 vxor_
 * @param string $key 索引键
 * @param mixed $value 键值
 * @param string $type 可选，ARRAY_A 或 空
 */
function vxor_update_option($field, $key, $value, $type = null) {
    $options = get_option( 'vxor_' . $field );

    if( $type == ARRAY_A ) {
        $options[$key][] = $value;
    } else {
        $options[$key] = $value;
    }

    update_option( 'vxor_'. $field, $options );
}

/**
 * 更新最大ID值进 vxor_step
 *
 * @global int $maxid
 * @param string $field
 * @param int $id
 */
function vxor_update_maxid($field, $id) {
    global $maxid;

    if( $id == $maxid ) {
        vxor_update_option( 'step', $field, $id );
    }
}

/**
 * 判断是否网址
 *
 * @since 1.0.0
 *
 * @param string $url 待验证的字符串
 * @return bool
 */
function vxor_is_url($url) {
    // 判断是否为网址，
    $is_url = preg_match('/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):/is', $url);

    if($is_url)
        return true;
    else
        return false;
}

/**
 * 去掉 开头结尾的 /
 *
 * @since 1.0.0
 *
 * @param string $string
 * @return string
 */
function vxor_remove_slash($string) {
    if (preg_match('/^\/?(.*?)\/?$/', $string, $strings)) {
        return $strings[1];
    }
}

/**
 * 去掉网址开始的协议
 *
 * @since 1.0.0
 *
 * @param string $url
 * @return string
 */
function vxor_remove_http($url) {
    $url = preg_replace('/^(https?|ftps?|mailto|news|irc|gopher|nntp|feed|telnet):\/\//', '', $url);
    return vxor_remove_slash( $url );
}

/**
 * Post 内容
 * apply_filter hook
 *
 * @since 1.0.0
 *
 * @param string $content
 * @return string $content after apply filters.
 */
function vxor_content($content) {
    extract( get_option('vxor_extend'), EXTR_SKIP );
    $content = str_replace($ext_url_old, $ext_url_new, $content);
    $content = str_replace($ext_post_old, $ext_post_new, $content);

    $search = array(
            'src="../attachments/',
    );
    $replace = array(
            'src="'. WP_CONTENT_URL . "/uploads/$ext_attach_path/",
    );
    $content = str_replace($search, $replace, $content);

    if($ext_url_new) {
        $content =  str_replace( get_option('siteurl') , $ext_site_url, $content);
    }

    $content = apply_filters('vxor_content', $content);
    return $content;
}

/**
 * UBB 代码转换
 *
 * @since 1.0.0
 *
 * @param string $content
 * @return string
 */
function vxor_content_ubb($content) {
    $search = array(
            '[hr]', '<br>','[u]','[/u]','[b]','[/b]','[i]','[/i]',
            '[code]','[/code]','[quote]','[/quote]','[list]','[/list]',
            '[strike]','[/strike]','[sup]','[/sup]','[sub]','[/sub]',
    );
    $replace = array(
            '<hr/>', '<br />','<em>','</em>','<strong>','</strong>','<span style="text-decoration: line-through;">','</span>',
            '<code>','</code>','<blockquote>','</blockquote>','<ul>','</ul>',
            '<del>','</del>','<sup>','</sup>','<sub>','</sub>',
    );
    $content = str_replace($search, $replace, $content);

    $pattern = array(
        '/\[list=([aA1]?)\](.+?)\[\/list\]/is',
        '/\[font=([^\[]*)\](.+?)\[\/font\]/is',
        '/\[color=([#0-9a-z]{1,10})\](.+?)\[\/color\]/is',
        '/\[email=([^\[]*)\]([^\[]*)\[\/email\]/is',
	'/\[email\]([^\[]*)\[\/email\]/is',
        '/\[size=(\d+)\](.+?)\[\/size\]/is',
        '/(\[align=)(left|center|right)(\])(.+?)(\[\/align\])/is',
        "/\[glow=(\d+)\,([0-9a-zA-Z]+?)\,(\d+)\](.+?)\[\/glow\]/is",
        '/\[img\](.+?)\[\/img\]/is',
        '/alt="open_img\(&#39;(.+?)&#39;\)"/is',
    );
    $replacement = array(
        '<ol type="\\1">\\2</ol>',
        '<font face="\\1">\\2</font>',
        '<font color="\\1">\\2</font>',
        '<a href="mailto:\\1">\\2</a>',
        '<a href="mailto:\\1">\\1</a>',
        '<font size="\\1">\\2</font>',
        '<div align="\\2">\\4</div>',
        '<span style="width:\\1;filter:glow(color=\\2,strength=\\3)">\\4</span>',
        '<img src="\\1">',
        '', //open_img
    );
    $content = preg_replace($pattern, $replacement, $content);

    $pattern = array(
        '/\[url=([^\[]*)\](.+?)\[\/url\]/eis',
        '/\[url\]([^\[]*)\[\/url\]/eis'
    );
    $replacement = array(
        'vxor_content_url("\\1", "\\2")',
        'vxor_content_url("\\1")',
    );

    $content = preg_replace($pattern, $replacement, $content);
    
    return $content;
}
add_filter('vxor_content', 'vxor_content_ubb');

/**
 * UBB 代码转换 URL 链接
 *
 * @since 1.0.0
 *
 * @param string $url
 * @param string $content
 * @return array
 */
function vxor_content_url($url, $content = '') {
    if( !vxor_is_url($url) )
        $url = 'http://' . $url;

    if( !$content )
        $content = $url;

    return '<a href="'. $url . '">'. $content .'</a>';
}

/**
 *　取得最大ID数
 *
 * @since 1.0.0
 *
 * @global Database Calss $sdb
 * @global int $maxid
 * @global string $dbprefix
 * @param string $table
 * @param string $field
 * @return int $maxid
 */
function vxor_get_maxid($table, $field) {
    global $sdb, $maxid, $dbprefix;

    if(!isset($maxid) || $maxid == 0) {
        $maxid =$sdb->get_var("SELECT max($field) FROM $dbprefix$table");
    }

    return $maxid;
}


################################################################################
## User 相关函数
################################################################################

/**
 * vXor Convertor User class extend WP_User.
 *
 * 参考自 WP_User 类，并做一定修改。
 *
 * @since 1.0.0
 * @see WP_User(), wp-includes/capabilities.php
 */
class vXor_User extends WP_User {
    /**
     * PHP4 Constructor - Sets up the object properties.
     *
     * Retrieves the userdata and then assigns all of the data keys to direct
     * properties of the object. Calls {@link WP_User::_init_caps()} after
     * setting up the object's user data properties.
     *
     * @since 1.0.0
     * @access public
     *
     * @param int|string $id User's ID or username
     * @param int $name Optional. User's username
     * @return vXor_User
     */
    function vXor_User( $id, $name = '' ) {

        if ( empty( $id ) && empty( $name ) )
            return;

        $this->data = vxor_get_userdata( $id );

        if ( empty( $this->data->ID ) )
            return;

        foreach ( get_object_vars( $this->data ) as $key => $value ) {
            $this->{$key} = $value;
        }

        $this->id = $this->ID;
        $this->_init_caps();
    }

    /**
     * Set the role of the user.
     *
     * This will remove the previous roles of the user and assign the user the
     * new one. You can set the role to an empty string and it will remove all
     * of the roles from the user.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $role Role name.
     */
    function set_role( $role ) {
        foreach ( (array) $this->roles as $oldrole )
            unset( $this->caps[$oldrole] );
        if ( !empty( $role ) ) {
            $this->caps[$role] = true;
            $this->roles = array( $role => true );
        } else {
            $this->roles = false;
        }
        vxor_update_usermeta( $this->ID, $this->cap_key, $this->caps );
        $this->get_role_caps();
        $this->update_user_level_from_caps();
        do_action( 'set_user_role', $this->ID, $role );
    }

}

/**
 * 往数据库中插入新用户
 *
 * 参考自 wp_insert_user()，并做一定的修改。
 *
 * Most of the $userdata array fields have filters associated with the values.
 * The exceptions are 'rich_editing', 'role', 'jabber', 'aim', 'yim',
 * 'user_registered', and 'ID'. The filters have the prefix 'pre_user_' followed
 * by the field name. An example using 'description' would have the filter
 * called, 'pre_user_description' that can be hooked into.
 *
 * $userdata 数组包含以下字段：
 * 'ID' - ID 统一编号。
 * 'user_pass' - 用户密码。
 * 'user_login' - 用户登录账号。
 * 'user_nicename' - 用户美化名称。默认为注册用户名。
 * 'user_url' - 用户个人网站。
 * 'user_email' - 用户电子邮箱。
 * 'display_name' - 显示在网站上的用户名称，默认为用户名。如果不想与注册用户名一致，可修改此项。
 * 'nickname' - 用户昵称，默认为用户注册名。
 * 'first_name' - 用户名字。
 * 'last_name' - 用户姓氏。
 * 'description' - 用户个人说明介绍。
 * 'rich_editing' - 是否允许使用富文本编辑器。
 * 'user_registered' - 用户注册时间，格式为 'Y-m-d H:i:s'。
 * 'role' - 用户组权限。
 *
 * @since 1.0.0
 * @version 1.1.0
 *
 * @see wp_insert_user(), wp-includes/registration.php
 * @uses $wpdb WordPress database layer.
 * @uses apply_filters() Calls filters for most of the $userdata fields with the prefix 'pre_user'. See note above.
 * @uses do_action() Calls 'profile_update' hook when updating giving the user's ID
 * @uses do_action() Calls 'user_register' hook when creating a new user giving the user's ID
 *
 * @param array $userdata An array of user data.
 * @return int The newly created user's ID.
 */
function vxor_insert_user( $userdata ) {
    global $wpdb;

    extract($userdata, EXTR_SKIP);

    $ID = (int)$ID;

    $user_login = apply_filters('pre_user_login', $user_login);

    if( empty( $user_pass) )
        $user_pass = wp_hash_password(wp_generate_password());
    
    if ( empty($user_nicename) )
        $user_nicename = sanitize_title( $user_login );
    $user_nicename = apply_filters('pre_user_nicename', $user_nicename);

    if ( empty($user_url) )
        $user_url = '';
    $user_url = apply_filters('pre_user_url', $user_url);

    if ( empty($user_email) )
        $user_email = '';
    $user_email = apply_filters('pre_user_email', $user_email);

    if ( empty($display_name) )
        $display_name = $user_login;
    $display_name = apply_filters('pre_user_display_name', $display_name);

    if ( empty($nickname) )
        $nickname = $user_login;
    $nickname = apply_filters('pre_user_nickname', $nickname);

    if ( empty($first_name) )
        $first_name = '';
    $first_name = apply_filters('pre_user_first_name', $first_name);

    if ( empty($last_name) )
        $last_name = '';
    $last_name = apply_filters('pre_user_last_name', $last_name);

    if ( empty($description) )
        $description = '';
    $description = apply_filters('pre_user_description', $description);

    if ( empty($rich_editing) )
        $rich_editing = 'true';

    if ( empty($comment_shortcuts) )
        $comment_shortcuts = 'false';

    if ( empty($admin_color) )
        $admin_color = 'fresh';
    $admin_color = preg_replace('|[^a-z0-9 _.\-@]|i', '', $admin_color);

    if ( empty($user_registered) )
        $user_registered = gmdate('Y-m-d H:i:s');

    $user_nicename_check = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->users}_tmp WHERE user_nicename = %s AND user_login != %s LIMIT 1" , $user_nicename, $user_login));

    if ( $user_nicename_check ) {
        $suffix = 2;
        while ($user_nicename_check) {
            $alt_user_nicename = $user_nicename . "-$suffix";
            $user_nicename_check = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->users}_tmp WHERE user_nicename = %s AND user_login != %s LIMIT 1" , $alt_user_nicename, $user_login));
            $suffix++;
        }
        $user_nicename = $alt_user_nicename;
    }

    $data = compact( 'ID','user_login', 'user_pass', 'user_email', 'user_url', 'user_nicename', 'display_name', 'user_registered' );
    $data = stripslashes_deep( $data );

    $wpdb->insert( $wpdb->users.'_tmp', $data );
    $user_id = (int) $wpdb->insert_id;

    vxor_update_usermeta( $user_id, 'first_name', $first_name);
    vxor_update_usermeta( $user_id, 'last_name', $last_name);
    vxor_update_usermeta( $user_id, 'nickname', $nickname );
    vxor_update_usermeta( $user_id, 'description', $description );
    vxor_update_usermeta( $user_id, 'rich_editing', $rich_editing);
    vxor_update_usermeta( $user_id, 'comment_shortcuts', $comment_shortcuts);
    vxor_update_usermeta( $user_id, 'admin_color', $admin_color);

    if ( isset($role) ) {
        $user = new vXor_User($user_id);
        $user->set_role($role);
    } else {
        $user = new vXor_User($user_id);
        $user->set_role(get_option('default_role'));
    }

    wp_cache_delete($user_id, 'users');
    wp_cache_delete($user_login, 'userlogins');


    do_action('user_register', $user_id);

    return $user_id;
}

/**
 * 更新用户 metadata。
 *
 * There is no need to serialize values, they will be serialized if it is
 * needed. The metadata key can only be a string with underscores. All else will
 * be removed.
 *
 * Will remove the metadata, if the meta value is empty.
 *
 * @since 1.0.0
 * @see update_usermeta(), wp-includes/user.php
 * @uses $wpdb WordPress database object for queries
 *
 * @param int $user_id User ID
 * @param string $meta_key Metadata key.
 * @param mixed $meta_value Metadata value.
 * @return bool True on successful update, false on failure.
 */
function vxor_update_usermeta( $user_id, $meta_key, $meta_value ) {
    global $wpdb;
    if ( !is_numeric( $user_id ) )
        return false;
    $meta_key = preg_replace('|[^a-z0-9_]|i', '', $meta_key);

    /** @todo Might need fix because usermeta data is assumed to be already escaped */
    if ( is_string($meta_value) )
        $meta_value = stripslashes($meta_value);
    $meta_value = maybe_serialize($meta_value);

    if (empty($meta_value)) {
        return delete_usermeta($user_id, $meta_key);
    }

    $cur = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->usermeta}_tmp WHERE user_id = %d AND meta_key = %s", $user_id, $meta_key) );

    if ( $cur )
        do_action( 'vxor_update_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value );

    if ( !$cur )
        $wpdb->insert($wpdb->usermeta.'_tmp', compact('user_id', 'meta_key', 'meta_value') );
    else if ( $cur->meta_value != $meta_value )
        $wpdb->update($wpdb->usermeta.'_tmp', compact('meta_value'), compact('user_id', 'meta_key') );
    else
        return false;

    wp_cache_delete($user_id, 'users');

    if ( !$cur )
        do_action( 'added_usermeta', $wpdb->insert_id, $user_id, $meta_key, $meta_value );
    else
        do_action( 'updated_usermeta', $cur->umeta_id, $user_id, $meta_key, $meta_value );

    return true;
}

/**
 * Retrieve user info by user ID.
 *
 * @since 1.0.0
 * @see wp_get_userdata(), wp-includes/pluggable.php
 *
 * @param int $user_id User ID
 * @return bool|object False on failure, User DB row object
 */
function vxor_get_userdata( $user_id ) {
    global $wpdb;

    $user_id = absint($user_id);
    if ( $user_id == 0 )
        return false;

    $user = wp_cache_get($user_id, 'users');

    if ( $user )
        return $user;

    if ( !$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->users}_tmp WHERE ID = %d LIMIT 1", $user_id)) )
        return false;

    _fill_user($user);

    return $user;
}

/**
 * 更新用户密码。
 * 原用户密码非 MD5 加密时，重置用户密码。
 *
 * @since 1.1.1
 *
 * @global WPDB $wpdb
 * @param mixed $user_name 用户名
 * @param mixed $user_pass 用户明文密码，更新后 MD5 加密。
 */
function vxor_update_userpass( $user_name, $user_pass ) {
    global $wpdb;
    $wpdb->query( "UPDATE {$wpdb->users}_tmp SET user_pass = MD5('$user_pass') WHERE user_login = '$user_name'");
}


################################################################################
## Taxonomy 相关函数
################################################################################

/**
 * 插入类别/分组
 *
 * 参考 wp_insert_category(), 并做一定修改。
 *
 * {@internal Missing Short Description}}
 *
 * @since 1.0.0
 * @version 1.1.0
 * @see wp_insert_category(), wp-admin/includes/taxonomy.php
 *
 * @param array $catarr
 * @param string $taxonomy
 * @param unknown_type $wp_error
 * @return false|int
 */
function vxor_insert_category($catarr, $taxonomy, $wp_error = false) {
    $cat_defaults = array('cat_ID' => 0, 'cat_name' => '', 'cat_description' => '', 'cat_nicename' => '', 'cat_parent' => '');
    $catarr = wp_parse_args($catarr, $cat_defaults);
    extract($catarr, EXTR_SKIP);

    if ( trim( $cat_name ) == '' )
        return;

    $term_id = (int) $cat_ID;

    $name = $cat_name;
    $description = $cat_description;
    $slug = $cat_nicename;
    $parent = ((int) $cat_parent) < 0 ? 0 : $cat_parent;

    $args = compact('term_id', 'name', 'slug', 'parent', 'description');

    $cat_ID = vxor_insert_term($cat_name, $taxonomy, $args);

    return $cat_ID['term_id'];
}

/**
 * Adds a new term to the database. Optionally marks it as an alias of an existing term.
 *
 * 参考自 wp_insert_term()，并做一定修改。
 *
 * Error handling is assigned for the nonexistance of the $taxonomy and $term
 * parameters before inserting. If both the term id and taxonomy exist
 * previously, then an array will be returned that contains the term id and the
 * contents of what is returned. The keys of the array are 'term_id' and
 * 'term_taxonomy_id' containing numeric values.
 *
 * It is assumed that the term does not yet exist or the above will apply. The
 * term will be first added to the term table and then related to the taxonomy
 * if everything is well. If everything is correct, then several actions will be
 * run prior to a filter and then several actions will be run after the filter
 * is run.
 *
 * The arguments decide how the term is handled based on the $args parameter.
 * The following is a list of the available overrides and the defaults.
 *
 * 'alias_of'. There is no default, but if added, expected is the slug that the
 * term will be an alias of. Expected to be a string.
 *
 * 'description'. There is no default. If exists, will be added to the database
 * along with the term. Expected to be a string.
 *
 * 'parent'. Expected to be numeric and default is 0 (zero). Will assign value
 * of 'parent' to the term.
 *
 * 'slug'. Expected to be a string. There is no default.
 *
 * If 'slug' argument exists then the slug will be checked to see if it is not
 * a valid term. If that check succeeds (it is not a valid term), then it is
 * added and the term id is given. If it fails, then a check is made to whether
 * the taxonomy is hierarchical and the parent argument is not empty. If the
 * second check succeeds, the term will be inserted and the term id will be
 * given.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 1.0.0
 * @uses $wpdb
 * @see wp_insert_term(), wp-includes/taxonomy.php
 *
 * @uses do_action() Calls 'create_term' hook with the term id and taxonomy id as parameters.
 * @uses do_action() Calls 'create_$taxonomy' hook with term id and taxonomy id as parameters.
 * @uses apply_filters() Calls 'term_id_filter' hook with term id and taxonomy id as parameters.
 * @uses do_action() Calls 'created_term' hook with the term id and taxonomy id as parameters.
 * @uses do_action() Calls 'created_$taxonomy' hook with term id and taxonomy id as parameters.
 *
 * @param int|string $term The term to add or update.
 * @param string $taxonomy The taxonomy to which to add the term
 * @param array|string $args Change the values of the inserted term
 * @return array|WP_Error The Term ID and Term Taxonomy ID
 */
function vxor_insert_term( $term, $taxonomy, $args = array() ) {
    global $wpdb;

    if ( ! is_taxonomy($taxonomy) )
        return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));

    if ( is_int($term) && 0 == $term )
        return new WP_Error('invalid_term_id', __('Invalid term ID'));

    if ( '' == trim($term) )
        return new WP_Error('empty_term_name', __('A name is required for this term'));

    $defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
    $args = wp_parse_args($args, $defaults);
    $args['name'] = $term;
    $args['taxonomy'] = $taxonomy;
    $args = sanitize_term($args, $taxonomy, 'db');
    extract($args, EXTR_SKIP);

    // expected_slashed ($name)
    $name = stripslashes($name);
    $description = stripslashes($description);

    if ( empty($slug) )
        $slug = sanitize_title($name);

    if ( ! is_term($slug) ) {
        $wpdb->insert( $wpdb->terms, compact( 'term_id', 'name', 'slug' ) );
        $term_id = (int) $wpdb->insert_id;
    }

    $tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM $wpdb->term_taxonomy AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id ) );

    if ( !empty($tt_id) )
        return;

    $wpdb->insert( $wpdb->term_taxonomy, compact( 'term_id', 'taxonomy', 'description', 'parent') + array( 'count' => 0 ) );
    $tt_id = (int) $wpdb->insert_id;

    do_action("create_term", $term_id, $tt_id, $taxonomy);
    do_action("create_$taxonomy", $term_id, $tt_id);

    $term_id = apply_filters('term_id_filter', $term_id, $tt_id);

    clean_term_cache($term_id, $taxonomy);

    do_action("created_term", $term_id, $tt_id, $taxonomy);
    do_action("created_$taxonomy", $term_id, $tt_id);

    return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}


################################################################################
## Posts & Attachments 相关函数
################################################################################

/**
 * 插入 Post
 *
 * $postarr 参数如下：
 * 'ID',                ID，唯一编号，将赋值予 $import_id
 * 'post_author',       作者
 * 'post_date',         可选，日期，格式为 Y-m-d h:m:s
 * 'post_date_gmt',     可选，GMT日期，格式为 Y-m-d h:m:s
 * 'post_content',      内容
 * 'post_title',        标题
 * 'post_excerpt',      可选，Post 摘要
 * 'post_status',       状态，默认值则根据设置中所设Post状态而定
 * 'comment_status',    评论状态，开启或关闭
 * 'ping_status',       Ping状态，开启或关闭，默认值则根据设置中所设Ping状态而定
 * 'post_password',     访问密码
 * 'post_name',         Slug
 * 'post_modified',     可选，修改日期，格式为 Y-m-d h:m:s
 * 'post_modified_gmt', 可选，修改日期，格式为 Y-m-d h:m:s
 * 'post_parent',       上级ID，默认0
 * 'guid',              Global Unique ID for referencing the attachment.
 * 'post_type',         Post 状态
 * 'post_mime_type',    Post 类型
 * 'comment_count',     评论数
 * 'post_category',     分类，
 * 'post_tag',          标签，多个标签以“,”半角逗号分隔
 *
 * @since 1.0.0
 * @version 1.1.0
 * @uses wp_insert_post()
 * @uses stick_post
 *
 * @param array $postarr
 * @param bool $wp_error Optional. Allow return of WP_Error on failure.
 * @return int|WP_Error The value 0 or WP_Error on failure. The post ID on success.
 */
function vxor_insert_post($postarr = array(), $wp_error = false) {
    global $wpdb;
    extract($postarr, EXTR_SKIP);

    $import_id = $ID;

    //Set the tag list
    if ( !isset($post_tag) )
        $tax_input = array();
    else
        $tax_input['post_tag'] = $post_tag;

    //Set category as array
    $category = array();
    if( !is_array( $post_category ) )
        $category[] = $post_category;
    $post_category = $category;

    $postarr = compact( array( 'import_id', 'post_category', 'tax_input',
            'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_content_filtered',
            'post_title', 'post_excerpt', 'post_status', 'post_type', 'comment_status',
            'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified',
            'post_modified_gmt', 'post_parent', 'menu_order', 'guid' ) );

    $post_ID = wp_insert_post($postarr, true);

    $wpdb->update($wpdb->posts, array('post_modified' => $post_modified), array('ID' => $post_ID) );
    $wpdb->update($wpdb->posts, array('post_modified_gmt' => $post_modified_gmt), array('ID' => $post_ID) );

    if( !is_wp_error($post_ID) && $sticky )
        stick_post( $post_ID );

    return $post_ID;

}

/**
 * 插入附件 到 posts 表
 *
 * $object 参数如下：
 * 'ID',                ID，唯一编号，将赋值予 $import_id
 * 'post_author',       作者
 * 'post_date',         可选，日期，格式为 Y-m-d h:m:s
 * 'post_date_gmt',     可选，GMT日期，格式为 Y-m-d h:m:s
 * 'post_content',      内容
 * 'post_title',        标题
 * 'post_name',         Slug
 * 'post_modified',     可选，修改日期，格式为 Y-m-d h:m:s
 * 'post_modified_gmt', 可选，修改日期，格式为 Y-m-d h:m:s
 * 'post_parent',       附属日志ID
 * 'guid',              Global Unique ID for referencing the attachment.
 * 'post_type',         attachment
 * 'post_mime_type',    Post 类型
 * 'comment_count',     评论数
 *
 * @since 1.0.0
 *
 * @param string|array $object Arguments to override defaults.
 * @param string $file Optional filename.
 * @return int Attachment ID.
 */
function vxor_insert_attachment($object, $file = false) {
    extract($object, EXTR_SKIP);

    $import_id = $ID;

    $data = compact( array( 'import_id',
            'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title',
            'post_modified', 'post_modified_gmt', 'post_parent','post_mime_type', 'guid' ) );

    if( !file_exists( $file) )
        $file = false;

    $post_ID = wp_insert_attachment($data, $file);

    return $post_ID;
}

/**
 * 判断附件的地址是否为网址。
 * 不是网址则在附件地址前加上 上传文件地址/附件设置的地址/。
 *
 * @since 1.0.0
 *
 * @param string $filename 文件名
 * @return string $guid 文件的 url 地址
 */
function vxor_get_attachment_guid($filename) {
    extract(get_option('vxor_extend'), EXTR_SKIP);

    if(vxor_is_url($filename)) {
        $guid = $filename;
        return $guid;
    }else {
        //本地附件
        $guid = WP_CONTENT_URL . '/uploads/'. ($ext_attach_path ? $ext_attach_path.'/' : '') . $filename;
        if( $ext_url_new )
            $guid = str_replace( get_bloginfo('url'), $ext_site_url, $guid );

        return $guid;
    }
}

/**
 * 取得附件的绝对路径
 *
 * @since 1.0.0
 *
 * @param string $filename
 * @return string 附件绝对路径
 */
function vxor_get_attachment_path($filename) {
    extract(get_option('vxor_extend'), EXTR_SKIP);

    $upload = wp_upload_dir();

    $path = $uploads['basedir'] . $ext_attach_path . '/' . $filename;

    return $path;
}

/**
 * 插入附件meta
 *
 *
 *
 * 如果 $file 附件存在，则根据 $file 来写入 meta；
 * 如果 $file 附件不存在，则根据 $metadata 写入 meta。
 * 都不存在，则不操作。　
 *
 * @since 1.0.0
 *
 * @param int $id
 * @param string $file 附件绝对路径
 * @param array $metadata 附件 meta
 * @return bool ID错误或不是图片则返回 false。
 */
function vxor_update_attachment_metadata($id, $file = false, $metadata = array()) {
    if(is_wp_error($id))
        return false;

    if( !preg_match('!^image/!', get_post_mime_type( get_post( $id ) )) ) {
        $is_image = false;
        $metadata = array();
    } else
        $is_image = true;

    if( file_exists( $file ) ) {
        $metadata = wp_generate_attachment_metadata( $id, $file );
    } else {
        update_attached_file( $id, $file );
    }

    wp_update_attachment_metadata( $id, $metadata );

    return $is_image;
}


################################################################################
## File 相关函数
################################################################################

/**
 * upload_mimes Filter
 *
 * @since 1.0.0
 * @version 1.2.0
 *
 * @param <type> $type
 * @return array 文件类型数组
 */
function vxor_upload_file_type( $type ) {
    $file_types = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'tif|tiff' => 'image/tiff',
            'ico' => 'image/x-icon',
            'asf|asx|wax|wmv|wmx' => 'video/asf',
            'avi' => 'video/avi',
            'divx' => 'video/divx',
            'flv' => 'video/x-flv',
            'mov|qt' => 'video/quicktime',
            'mpeg|mpg|mpe' => 'video/mpeg',
            'txt|c|cc|h' => 'text/plain',
            'rtx' => 'text/richtext',
            'css' => 'text/css',
            'htm|html' => 'text/html',
            'mp3|m4a' => 'audio/mpeg',
            'mp4|m4v' => 'video/mp4',
            'ra|ram' => 'audio/x-realaudio',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'mid|midi' => 'audio/midi',
            'wma' => 'audio/wma',
            'rtf' => 'application/rtf',
            'js' => 'application/javascript',
            'pdf' => 'application/pdf',
            'doc|docx' => 'application/msword',
            'pot|pps|ppt|pptx' => 'application/vnd.ms-powerpoint',
            'wri' => 'application/vnd.ms-write',
            'xla|xls|xlsx|xlt|xlw' => 'application/vnd.ms-excel',
            'mdb' => 'application/vnd.ms-access',
            'mpp' => 'application/vnd.ms-project',
            'swf' => 'application/x-shockwave-flash',
            'class' => 'application/java',
            'tar' => 'application/x-tar',
            'zip' => 'application/zip',
            'gz|gzip' => 'application/x-gzip',
            'exe' => 'application/x-msdownload',
            // openoffice formats
            'odt' => 'application/vnd.oasis.opendocument.text',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'odb' => 'application/vnd.oasis.opendocument.database',
            'odf' => 'application/vnd.oasis.opendocument.formula',

            //added formats
            'rar' => 'application/rar',
            'xml' => 'text/xml',
    );

    return $file_types;
}
add_filter('upload_mimes', 'vxor_upload_file_type');


################################################################################
## Bookmark 相关函数
################################################################################

/**
 * 插入链接。
 *
 * 参考自 wp_insert_link(), 并做一定修改。
 *
 * $linkdata 中包含以下字段：
 * 'link_id',           链接的ID，唯一编号
 * 'link_url',          Web 地址
 * 'link_name',         名称
 * 'link_image',        图片地址
 * 'link_target',       连接目标：_blank, _top, _none
 * 'link_description',  链接描述
 * 'link_visible',      是否可见
 * 'link_owner',        所属者
 * 'link_rating',       等级
 * 'link_updated',
 * 'link_rel',          链接关系 (XFN)
 * 'link_notes',        注释
 * 'link_rss',          RSS地址
 *
 * {@internal Missing Short Description}}
 *
 * @since 1.0.0
 * @see wp_insert_link(), wp-admin/includes/bookmark.php
 *
 * @param array $linkdata
 * @return int
 */
function vxor_insert_link( $linkdata, $wp_error = false ) {
    global $wpdb, $current_user;

    $defaults = array( 'link_id' => 0, 'link_name' => '', 'link_url' => '', 'link_rating' => 0 );

    $linkdata = wp_parse_args( $linkdata, $defaults );
    $linkdata = sanitize_bookmark( $linkdata, 'db' );

    extract( stripslashes_deep( $linkdata ), EXTR_SKIP );

    if ( trim( $link_name ) == '' ) {
        if ( trim( $link_url ) != '' ) {
            $link_name = $link_url;
        } else {
            return 0;
        }
    }

    if ( trim( $link_url ) == '' )
        return 0;

    if ( empty( $link_rating ) )
        $link_rating = 0;

    if ( empty( $link_image ) )
        $link_image = '';

    if ( empty( $link_target ) )
        $link_target = '';

    if ( empty( $link_visible ) )
        $link_visible = 'Y';

    if ( empty( $link_owner ) )
        $link_owner = $current_user->id;

    if ( empty( $link_notes ) )
        $link_notes = '';

    if ( empty( $link_description ) )
        $link_description = '';

    if ( empty( $link_rss ) )
        $link_rss = '';

    if ( empty( $link_rel ) )
        $link_rel = '';

    // Make sure we set a valid category
    if ( ! isset( $link_category ) || 0 == count( $link_category ) ) {
        $link_category = array( get_option( 'default_link_category' ) );
    }

    if ( false === $wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->links (link_id, link_url, link_name, link_image, link_target, link_description, link_visible, link_owner, link_rating, link_rel, link_notes, link_rss) VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            $link_id, $link_url,$link_name, $link_image, $link_target, $link_description, $link_visible, $link_owner, $link_rating, $link_rel, $link_notes, $link_rss ) ) ) {
        if ( $wp_error )
            return new WP_Error( 'db_insert_error', __( 'Could not insert link into the database' ), $wpdb->last_error );
        else
            return 0;
    }
    $link_id = (int) $wpdb->insert_id;

    vxor_set_link_cats( $link_id, $link_category );

    do_action( 'add_link', $link_id );

    clean_bookmark_cache( $link_id );

    return $link_id;
}

/**
 *
 * 参考自 wp_link_cats(), 并做一定修改。
 *
 * {@internal Missing Short Description}}
 *
 * @since unknown
 * @see wp_link_cats(), wp-admin/includes/bookmark.php
 *
 * @param unknown_type $link_id
 * @param unknown_type $link_categories
 */
function vxor_set_link_cats( $link_id = 0, $link_categories = array() ) {
    // If $link_categories isn't already an array, make it one:
    if ( !is_array( $link_categories ) || 0 == count( $link_categories ) )
        $link_categories = array( get_option( 'default_link_category' ) );

    $link_categories = array_map( 'intval', $link_categories );
    $link_categories = array_unique( $link_categories );

    wp_set_object_terms( $link_id, $link_categories, 'link_category' );

    clean_bookmark_cache( $link_id );
}	// wp_set_link_cats()


################################################################################
## Comment 相关函数
################################################################################

/**
 * 插入评论
 *
 * 参考自 wp_insert_comment(), 并做一定修改
 *
 * The available comment data key names are 'comment_author_IP', 'comment_date',
 * 'comment_date_gmt', 'comment_parent', 'comment_approved', and 'user_id'.
 *
 * @since 1.0.0
 * @see wp_insert_comment(), wp-includes/comment.php
 * @uses $wpdb
 *
 * @param array $commentdata Contains information on the comment.
 * @return int The new comment's ID.
 */
function vxor_insert_comment($commentdata) {
    global $wpdb;
    extract(stripslashes_deep($commentdata), EXTR_SKIP);

    if ( ! isset($comment_ID) )
        return;
    if ( ! isset($comment_author_IP) )
        $comment_author_IP = '';
    if ( ! isset($comment_date) )
        $comment_date = current_time('mysql');
    if ( ! isset($comment_date_gmt) )
        $comment_date_gmt = get_gmt_from_date($comment_date);
    if ( ! isset($comment_parent) )
        $comment_parent = 0;
    if ( ! isset($comment_approved) )
        $comment_approved = 1;
    if ( ! isset($comment_karma) )
        $comment_karma = 0;
    if ( ! isset($user_id) )
        $user_id = 0;
    if ( ! isset($comment_type) )
        $comment_type = '';

    $data = compact('comment_ID', 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_author_IP', 'comment_date', 'comment_date_gmt', 'comment_content', 'comment_karma', 'comment_approved', 'comment_agent', 'comment_type', 'comment_parent', 'user_id');

    $wpdb->insert($wpdb->comments, $data);

    $id = (int) $wpdb->insert_id;

    if ( $comment_approved == 1 )
        wp_update_comment_count($comment_post_ID);

    $comment = get_comment($id);
    do_action('vxor_insert_comment', $id, $comment);

    return $id;
}


################################################################################
## Page 函数
################################################################################

/**
 * 插入 关于 页面
 *
 * @since 1.1.0
 *
 * @global int $user_ID 用户 ID
 * @return NULL
 */
function vxor_insert_page_about() {
    extract(get_option('vxor_extend'), EXTR_SKIP);
    if( !$ext_page_about )
        return;

    global $wpdb, $user_ID;

    $now = date('Y-m-d H:i:s');
    $now_gmt = gmdate('Y-m-d H:i:s');

    $wpdb->insert( $wpdb->posts, array(
            'post_author' => $user_ID,
            'post_date' => $now,
            'post_date_gmt' => $now_gmt,
            'post_content' => __('This is an example of a WordPress page, you could edit this to put information about yourself or your site so readers know where you are coming from. You can create as many pages like this one or sub-pages as you like and manage all of your content inside of WordPress.'),
            'post_excerpt' => '',
            'post_title' => __('About'),
            'post_name' => 'about',
            'post_modified' => $now,
            'post_modified_gmt' => $now_gmt,
            'post_type' => 'page',
    ));
    $guid =  $ext_site_url . '/?page_id='. $wpdb->insert_id;

    $wpdb->update( $wpdb->posts, array( 'guid' => $guid ), array( 'ID' => $wpdb->insert_id ) );
    
}

/**
 * 插入 留言簿 页面
 * 
 * @since 1.1.0
 *
 * @global int $user_ID 用户 ID
 * @return NULL
 */
function vxor_insert_page_guest() {
    extract(get_option('vxor_extend'), EXTR_SKIP);
    if( !$ext_page_guest )
        return;

    global $wpdb, $user_ID;

    $now = date('Y-m-d H:i:s');
    $now_gmt = gmdate('Y-m-d H:i:s');

    $wpdb->insert( $wpdb->posts, array(
            'post_author' => $user_ID,
            'post_date' => $now,
            'post_date_gmt' => $now_gmt,
            'post_content' => __('This is <strong>GuestBook</strong> page, an example of a WordPress page, you could edit this to put information about yourself or your site so readers know where you are coming from. You can create as many pages like this one or sub-pages as you like and manage all of your content inside of WordPress.', VXOR),
            'post_excerpt' => '',
            'post_title' => __('GuestBook'),
            'post_name' => 'guestbook',
            'post_modified' => $now,
            'post_modified_gmt' => $now_gmt,
            'post_type' => 'page',
    ));
    $guid =  $ext_site_url . '/?page_id='. $wpdb->insert_id;

    $wpdb->update( $wpdb->posts, array( 'guid' => $guid ), array( 'ID' => $wpdb->insert_id ) );
    vxor_update_option( 'step', 'page_guest_id', $wpdb->insert_id );
    
}

################################################################################
## 其他函数
################################################################################

/**
 * 根据用户名取得用户 ID
 *
 * @since 1.1.0
 *
 * @param string $userName
 * @return int $authorID
 */
function vxor_get_author_id($Name) {
    global $wpdb;

    $user = $wpdb->get_row("SELECT ID FROM {$wpdb->users}_tmp WHERE `user_login` = '$Name'", ARRAY_A);

    return $user['ID'];
}

/**
 * 根据附件所属日志 ID，取得该篇日志的作者 ID。
 *
 * @since 1.1.0
 *
 * @param int $post_id 附件所属日志 ID
 * @return int 作者 ID
 */
function vxor_get_attachment_author_id($post_id) {
    $post_data = get_post($post_id, ARRAY_A);
    if($post_data)
        return $post_data['post_author'];
}

?>