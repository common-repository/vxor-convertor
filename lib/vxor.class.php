<?php

require_once dirname(__FILE__) . '/db.class.php';
require_once dirname(__FILE__) . '/vxor.func.php';
require_once dirname(__FILE__) . '/convert.func.php';

class vXorConvertor {

    /* 插件文件夹名 */
    var $plugin = '';

    /* Config.php 文件头信息 */
    var $config = array();

    /* 网址中的 action 值 */
    var $action = '';

    /* 上一次 action 值 */
    var $last_action = '';

    /* 转换插件的所有步骤、名称 */
    var $steps = array();


    ############################################################################
    ## WordPress 插件与导入函数
    ############################################################################

    function __construct() {
        $this->constances();

        $this->i18n();
    }

    /**
     * 定义常量.
     *
     * @since 1.0.0
     */
    function constances() {
        define( 'VXOR', 'vxor-convertor' );
        define( 'VXOR_DIR', WP_PLUGIN_DIR . '/'. VXOR );
        define( 'VXOR_TEMP_DIR', VXOR_DIR . '/_temp' );
        define( 'VXOR_LIB_DIR', VXOR_DIR . '/lib' );
        define( 'VXOR_PLUGIN_DIR', VXOR_DIR . '/plugins' );

        define( 'VXOR_URL', WP_PLUGIN_URL . '/'. VXOR );
        define( 'VXOR_JS_URL', VXOR_URL . '/lib/js' );
        define( 'VXOR_CSS_URL', VXOR_URL . '/lib/css' );

        define( 'VXOR_DEBUG', true );
    }

    /**
     * 多国语言.
     *
     * @since 1.0.0
     */
    function i18n() {
        load_plugin_textdomain( VXOR, false, '/vxor-convertor/i18n' );
    }

    /**
     * vXor Convertor 类入口.
     *
     * @since 1.0.0
     */
    function dispatch() {
        add_action( 'admin_init', array( $this, 'register_plugins' ));
        add_action( 'admin_head', array( $this, 'hookHead' ));
    }

    /**
     * 注册 vXor Convertor 的转换插件.
     *
     * @since 1.0.0
     */
    function register_plugins() {
        if( !$plugins = _vxor_get_plugins() )
            return;

        foreach( $plugins as $plugin ) {
            if( $config = _vxor_get_plugin_config( $plugin ) )
                register_importer( $plugin, 'vXor: '.$config['BlogName'], $config['Description'], array( $this, 'start' ));
        }
    }

    /**
     * admin_head hooks.
     *
     * @since 1.0.0
     */
    function hookHead() {
        echo '<link rel="stylesheet" type="text/css" href="'. VXOR_CSS_URL .'/vxor.css" />'."\n";
        echo '<script type="text/javascript" src="'. VXOR_JS_URL .'/jquery.js"></script>'."\n";
        echo '<script type="text/javascript" src="'. VXOR_JS_URL .'/vxor.js"></script>'."\n";
    }


    ############################################################################
    ## 输出 HTML.
    ############################################################################

    /**
     * 输出 <![CDATA[...]]>
     *
     * @since 1.0.0
     */
    function cdata() {
        $url = get_bloginfo('url') .'/wp-admin/admin.php?import='. $this->plugin;

        $ext_post_id =_vxor_blog_extend_post_replace();
        ?>
<script type="text/javascript">
    //<![CDATA[
    var vxor_url = "<?php echo $url; ?>";
    var vxor_ext_post_id = <?php echo $ext_post_id; ?>;
    var vxor_ext_post_title = "<?php _e('Post Replacement', VXOR); ?>";
    //]]>
</script>
        <?php
    }

    /**
     * 输出 header
     *
     * @since 1.0.0
     */
    function header() {
        $this->cdata();
        ?>

<div class="wrap">
            <?php screen_icon(); ?>
    <h2>vXor Convertor</h2>
    <div class="error"><?php printf(__('<strong>Important:</strong> before converting, please <u>backup your database</u>. For help with convert, visit the <a href="%1$s" title="%2$s">plugin site</a>.', VXOR), $this->config['URI'], __('Visit plugin site') ); ?></div>
    <h3><?php echo $this->config['Title']; ?></h3>
    <p><?php echo $this->config['Meta']; ?></p>
    <div class="narrow">
                <?php
            }

    /**
     * 输出 footer
     *
     * @since 1.0.0
     */
    function footer() {
                ?>
    </div><!-- .narrow -->
</div><!-- .wrap -->
        <?php
    }


    ############################################################################
    ## 类通用函数
    ############################################################################

    /**
     * 显示扩展设置
     *
     * @since 1.2.0
     */
    function show_extend_form(){
        // Show extend config form.
        ?>
<p><?php _e('Extend setting are optional.<br>
For more infomation, please read the plugin convert readme.', VXOR); ?></p>
<form id="vxorform" action="admin.php?import=<?php echo $this->plugin ?>&amp;action=extend" method="post">
            <?php wp_nonce_field('import-'.$this->plugin); ?>
            <?php _vxor_blog_extend_form($this); ?>
    <p class="submit"><input id="extend" type="submit" name="submit" class="button" value="<?php _e('Save Extend', VXOR); ?>"></p>
</form>
        <?php
    }

    /**
     * 保存扩展设置
     *
     * @since 1.2.0
     */
    function save_extend_form() {
        if($this->last_action == 'config') {
            //保存 extend
            $this->save_extend_settings();
            printf('<p>' . __('Extend setting were saved.', VXOR) . '</p>');
        }

        printf('<p>'. __("Click 'Previous' for modifying extend setting.<br>
Click 'Start Convert' for converting.
            ", VXOR) . '</p>');
        ?>
<p class ="submit"><a href="admin.php?import=<?php echo $this->plugin ?>&amp;action=config" id="config" class="button"><?php _e('Previous', VXOR); ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="admin.php?import=<?php echo $this->plugin ?>&amp;action=convert" id="convert" class="button"><?php _e('Start Convert', VXOR); ?></a></p>
        <?php        
    }

    /**
     * 保存扩展设置
     *
     * @since 1.2.0
     */
    function save_extend_settings() {
        foreach ( $_POST as $key => $value ) {
            if ( !preg_match('/ext_(.*)/i', $key ) || empty( $value ) )
                continue;

            if( $key == 'ext_attach_path' ) {
                $ext[$key] = vxor_remove_slash($value);
            } else if( $key == 'ext_url_old' || $key == 'ext_url_new' ) {
                $ext[$key] = vxor_remove_http($value);
            } else {
                $ext[$key] = $value;
            }
        }

        if( empty($ext['ext_url_new']) ) {
            $ext['ext_url_old'] = '';
            $ext['ext_site_url'] = get_bloginfo('url');
        } else {
            $ext['ext_site_url'] = 'http://'.$ext['ext_url_new'];
        }

        if( !empty( $ext['ext_post_old'] ) ) {
            foreach( $ext['ext_post_old'] as $key => $value ) {
                if( empty($value) ) {
                    unset($ext['ext_post_old'][$key]);
                    unset($ext['ext_post_new'][$key]);
                }
            }
            array_multisort($ext['ext_post_old'], $ext['ext_post_new']);
        }

        update_option('vxor_extend', $ext);
    }

    /**
     * 显示结束信息
     *
     * @since 1.2.0
     */
    function finish() {
                ?>
<p><?php _e("<p>Converted!</p>
<ul><li>Click 'Previous' for reconverting.</li>
<li>Click 'Finish' for ending.</li>
<li>After finish, you have to login with your original blog account.</li>
<li>For more infomation, please read the plugin convert readme.</li></ul></p>", VXOR); ?></p>
<p class ="submit"><a href="admin.php?import=<?php echo $this->plugin ?>&amp;action=extend" id="extend" class="button"><?php _e('Previous', VXOR); ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="admin.php?import=<?php echo $this->plugin ?>&amp;action=clean" id="clean" class="button"><?php _e('Finish', VXOR); ?></a></p>

        <?php
    }

    /**
     * 清理工作
     *
     * @since 1.2.0
     *
     * @param bool $del_users 是否删除用户表
     */
    function cleanup($del_user = false) {
        /* 清理 wp_options */
        $options = array('config', 'extend', 'action', 'stats', 'step');
        foreach ( $options as $option ) {
            $option = 'vxor_' . $option;
            delete_option( $option );
        }

        /* 删除 wp_users、wp_usermeta，重命名 wp_users_tmp、wp_usermeta 为前者 */
        _vxor_db_del($del_user);

        /* 卸载 vXor Convertor */
        deactivate_plugins(VXOR . '/'. VXOR . '.php');

        do_action('import_done', $this->plugin);
        echo '<h3>'.sprintf(__('All done.').' <a href="%s">'.__('Have fun!').'</a>', get_option('home')).'</h3>';
    }
    

    ############################################################################
    ## BSP 转换步骤及函数
    ############################################################################

    /**
     * BSP 欢迎界面及选择文件
     *
     * @since 1.2.0
     */
    function bsp_greet() {
        ?>
<form id="vxorform" action="admin.php?import=<?php echo $this->plugin ?>&amp;action=config" method="post">
    <?php echo _vxor_select_html(vxor_get_files(VXOR_TEMP_DIR, 'xml'), 'bsp', 'bsp'); ?>
    <p class="submit"><input id="config" type="submit" name="submit" class="button" value="<?php _e('Check XML file', VXOR); ?>"></p>
</form>
        <?php
    }

    /**
     * 检测 xml 文件，并显示扩展设置
     *
     * @since 1.2.0
     */
    function bsp_config(){
        // TODO: 读取 xml 文件，读取 Tag 表
        // TODO: 检测 xml 是否有效

        $this->show_extend_form();
    }

    /**
     * 保存扩展设置
     *
     * @since 1.2.0
     */
    function bsp_extend() {
        return $this->save_extend_form();
    }

    /**
     * 转换过程
     *
     * @since 1.2.0
     *
     * @return WP_Error 转换过程出错则返回 WP_Error 类。
     */
    function bsp_convert() {
        // TODO: 转换过程
    }

    /**
     * 转换结束
     *
     * @since 1.2.0
     */
    function bsp_finish() {
        return $this->finish();
    }

    /**
     * 清理工作
     *
     * @since 1.2.0
     */
    function bsp_cleanup() {
        return $this->cleanup();
    }


    ############################################################################
    ## 独立博客转换步骤及函数
    ############################################################################

    /**
     * 欢迎信息及数据库设置
     *
     * @since 1.0.0
     */
    function blog_greet() {
        ?>
<p><?php printf(__('Howdy! This imports %1$s from %2$s %3$s into this blog.', VXOR), implode(', ', $this->steps['name']), $this->config['BlogName'], $this->config['BlogVersion']); ?></p>
<?php if($this->config['BlogType'] == 'access') { ?>
<p><strong><?php printf(__('Requirements: Microsoft Access Driver or Microsoft.Jet.OLEDB.4.0 Driver installed in this server.', VXOR)) ?></strong></p>
<?php } ?>

<form id="vxorform" action="admin.php?import=<?php echo $this->plugin ?>&amp;action=config" method="post">
            <?php wp_nonce_field('import-'.$this->plugin); ?>
            <?php _vxor_blog_db_form($this->config); ?>
    <p class="submit"><input id="config" type="submit" name="submit" class="button" value="<?php _e('Check Connect Database', VXOR); ?>"></p>
</form>
        <?php
    }

    /**
     * 保存数据库设置信息到数据库，并显示扩展设置
     *
     * @since 1.0.0
     * @version 1.2.0
     *
     * @return WP_Error 数据库连接错误则返回 WP_Error 类。
     */
    function blog_config() {
        if( $this->last_action == '' || $this->last_action == 'config' ) {
            if(! $this->blog_save_config()) {
                $message = '<div id="vxor_db_fail">'. __("
<h1>Error establishing a database connection</h1>
<ul>
	<li>Are you sure you have the correct username and password?</li>
	<li>Are you sure that you have typed the correct hostname?</li>
	<li>Are you sure that the database server is running?</li>
</ul>
<p>If you're unsure what these terms mean you should probably contact your host.</p>
                ", VXOR) .'<a href="admin.php?import='. $this->plugin .'" class="button">'. __('Previous', VXOR) .'</a></div>';
                return new WP_Error('db_connect_fail', $message );
            }
        }

        $this->show_extend_form();
    }

    /**
     * 保存扩展设置
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function blog_extend() {
        return $this->save_extend_form();
    }

    /**
     * 转换过程
     *
     * @since 1.0.0
     * @version 1.1.0
     *
     * @return WP_Error 转换过程出错则返回 WP_Error 类。
     */
    function blog_convert() {
        global $wpdb, $sdb, $maxid, $dbprefix;
        extract( get_option('vxor_config') );
        extract( get_option('vxor_extend'), EXTR_SKIP );
        if($vxor_step = get_option('vxor_step'))
            extract($vxor_step, EXTR_SKIP);

        $step = isset($_GET['step']) ? $_GET['step'] : 0;
        $start = isset($_GET['start']) && $_GET['start'] > 1 ? $_GET['start'] : 1;
        $end = $start + $dbrpp - 1;
        $maxid = isset($_GET['maxid']) ? $_GET['maxid'] : 0;
        $converted = false;

        $sdb = $this->blog_dbconnection();
        _vxor_load_functions($this->plugin);

        // 转换过程
        if($step) {
            $step_num = $this->steps['step'][$step];

            if( file_exists( VXOR_PLUGIN_DIR . "/$this->plugin/extend_step_$step_num.php")) {
                // 载入扩展版转换步骤
                $vxor_errors = (require_once VXOR_PLUGIN_DIR . "/$this->plugin/extend_step_$step_num.php");
            } else {
                // 载入普通版转换步骤
                if(file_exists( VXOR_PLUGIN_DIR ."/$this->plugin/step_$step_num.php"))
                    $vxor_errors = (require_once VXOR_PLUGIN_DIR ."/$this->plugin/step_$step_num.php");
            }

        } else {
            // TODO !isste($step), 初始化数据库
            _vxor_db_init(true);
            $vxor_next_step = 1;
        }

        if( is_wp_error($vxor_errors) )
            return $vxor_errors;

        if($end < $maxid)
            $converted = true;

        if( $converted ) {
            printf( __('It\'s converting %s data... ', VXOR).'<br />', $this->steps['name'][$step] );
            printf( __('%s to %s were conveted.', VXOR ), $start, $end );
            $vxor_next_step ="$step&start=". ($end+1) ."&maxid=$maxid";
        } else {
            if( $step ) {
                if( $step > count($this->steps['step']) ) {
                    _e('Completed!');
                    $vxor_next_step = 'finish';
                } else {
                    printf( __('It\'s converting %s data... ', VXOR).'<br />', $this->steps['name'][$step] );
                    if( $maxid )
                        printf( __('%s to %s were conveted.', VXOR ), $start, $maxid );
                    $vxor_next_step = ++$step;
                }
            }
        }

        echo '<div><input id="vxor_next_step" type="hidden" value="'. $vxor_next_step .'" /></div>';
    }

    /**
     * 转换结束
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function blog_finish() {
        return $this->finish();
    }

    /**
     * 清理工作
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function blog_cleanup() {
        return $this->cleanup(true);
    }


    /**
     * 检测 config.php 中的指定变量是否已定义
     * is_config() 更改为 blog_check_config()
     *
     * @since 1.1.0
     * @version 1.2.0
     *
     * @return WP_Error 指定变量未定义，返回 WP_Error 类，
     */
    function blog_check_config() {
        require_once( VXOR_PLUGIN_DIR . "/$this->plugin/config.php" );

        $keys = array( 'dbcheck', 'extends' );
        foreach ( $keys as $key ) {
            $vxor_key = 'vxor_' . $key;

            if( $this->config['BlogType'] != 'bsp' && $key == 'dbcheck' ) {
                if( empty($$vxor_key) )
                    return new WP_Error( 'key_undefined_or_empty', sprintf(__('%s variable undefined or empty value in config.php.', VXOR), '$'.$vxor_key ) );

                foreach ( array( 'table', 'field' ) as $inkey ) {
                    if( !array_key_exists($inkey, $$vxor_key ) )
                        return new WP_Error( 'key_missing', sprintf( __( '<strong>$vxor_dbcheck</strong> missing key <strong>%s</strong>', VXOR ), $inkey) );
                }
            } elseif ( $key == 'extends' ) {
                // $vxor_extends = array( array( $name, $ID, $type, $value = '' ) );
                
                $new_extends = array();     // 存储新 extends 值，去掉无效的值。
                $extends_id = array();      // extends 各项的 ID，用于检测是否有重复 ID。
                $iLine = $i = 0;            // $iLine 行数，$i 计数器。
                if( empty($$vxor_key) )
                    continue;
                foreach ($$vxor_key as $inkey ) {
                    $iLine++;
                    if( empty($inkey[1]) || empty($inkey[2]) )  // $ID 和 $type 为空
                        continue;
                    if( in_array($inkey[1], $extends_id ) )     // $ID 重复
                        return new WP_Error( 'duplicate_ID', sprintf( '"%1$s" in Line %2$s was defined!', $inkey[1], $iLine ) );

                    if( !empty($inkey[0] ) )
                        $new_extends[$i]['name'] = $inkey[0];
                    else    // $name 为空，取 $ID 值
                        $new_extends[$i]['name'] = $inkey[1];

                    $extends_id[] = $new_extends[$i]['ID'] = $inkey[1];
                    $new_extends[$i]['type'] = $inkey[2];
                    $new_extends[$i]['value'] = $inkey[3];
                    $i++;
                }
                $$vxor_key = $new_extends;
            }

            $this->$vxor_key = $$vxor_key;
        }
    }

    /**
     * 保存数据库设置信息并检查数据库连接
     *
     * @since 1.0.0
     *
     * @return bool 连接成功返回 true，失败返回 false。
     */
    function blog_save_config() {
        // 写入数据库连接信息
        $keys = array( 'dbuser', 'dbpassword', 'dbname', 'dbhost', 'dbprefix', 'dbrpp' );
        foreach( $keys as $key ) {
            if( !$_POST[$key] )
                continue;
            $config[$key] = $_POST[$key];
        }
        update_option( 'vxor_config', $config );
        extract(get_option('vxor_config'), EXTR_SKIP);

        // 检测数据库连接
        $sdb = $this->blog_dbconnection();

        if( !is_wp_error( $sdb ) && $sdb->get_results('SELECT '. $this->vxor_dbcheck['field'] .' FROM '.$dbprefix . $this->vxor_dbcheck['table'] , ARRAY_A) ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 数据库连接
     *
     * @since 1.0.0
     *
     * @return object 返回 db_mysql 或 db_access 类。
     */
    function blog_dbconnection() {
        $config = get_option('vxor_config');
        extract($config, EXTR_SKIP);

        $sdb = null;

        if( $this->config['BlogType'] == 'mysql' ) {
            $sdb = new db_mysql($dbuser, $dbpassword, $dbname, $dbhost);
        } else if( $this->config['BlogType'] == 'access' ) {
            $dbhostpath = VXOR_TEMP_DIR . '/' . $dbhost;
            if( ! file_exists($dbhostpath) ) {
                return new WP_Error( 'database_no_exists', __('Database no exists.', VXOR ) );
            }
            $sdb = new db_access($dbhostpath, $dbuser, $dbpassword);
        }

        return $sdb;
    }


    ############################################################################
    ## vXor Convertor Main.
    ############################################################################

    /**
     * vXor Convertor 入口
     *
     * @since 1.0.0
     * @version 1.2.0
     */
    function start() {
        $this->plugin = $_GET['import'];
        $this->action = empty($_GET['action']) ? '' : $_GET['action'];
        $this->config = _vxor_get_plugin_config( $this->plugin );

        $this->header();
        
        if( ! vxor_is_error( $this->blog_check_config() ) )
            return;

        if( $this->config['BlogType'] == 'bsp' ) {
            $results = $this->start_bsp();
        } else {
            $results = $this->start_blog();
        }

        vxor_is_error( $results );

        $this->footer();
    }

    /**
     * BSP 转换步骤入口
     *
     * @since 1.0.0
     * @version 1.2.0
     *
     * @return WP_Error 过程出错则返回 WP_Error。
     */
    function start_bsp() {

        switch( $this->action ) {
            case 'config':
                return $this->bsp_config();
                break;

            case 'extend':
                return $this->bsp_extend();
                break;

            case 'convert':
                return $this->bsp_convert();
                break;

            case 'finish':
                return $this->bsp_finish();
                break;

            case 'clean':
                return $this->bsp_cleanup();
                break;
            
            default:
                $this->bsp_greet();
                break;
        }
    }

    /**
     * 独立博客转换步骤入口
     *
     * @since 1.0.0
     *
     * @return WP_Error 过程出错则返回 WP_Error。
     */
    function start_blog() {        
        if( !vxor_is_error( $this->steps = _vxor_get_plugin_steps($this->plugin) ) )
            return;

        $this->last_action = _vxor_get_last_action($this->action);

        $step_result = NULL;
        switch( $this->action ) {
            case 'config':
                return $this->blog_config();
                break;

            case 'extend':
                return $this->blog_extend();
                break;

            case 'convert':
                return $this->blog_convert();
                break;

            case 'finish':
                return $this->blog_finish();
                break;

            case 'clean':
                return $this->blog_cleanup();
                brak;

            default:
                $this->blog_greet();
                break;
        }
    }

}

?>