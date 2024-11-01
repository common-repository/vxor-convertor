<?php
/**
 * vXor Step: Members
 *
 * vxor_insert_user($userdata) 插入用户数据
 */

$sUsers = $sdb->get_results("SELECT * FROM {$dbprefix}member ORDER BY mem_ID", ARRAY_A);

if( empty($sUsers) )
    return;

foreach ( $sUsers as $sUser ) {
    $userdata = array(
            'ID'              => $sUser['mem_ID'],
            'user_login'      => $sUser['mem_Name'],
            'user_nicename'   => $sUser['mem_Name'],
            'user_url'        => $sUser['mem_HomePage'],
            'user_email'      => $sUser['mem_Email'],
            'display_name'    => $sUser['mem_Name'],
            'user_registered' => $sUser['mem_RegTime'],
            'role'            => pj_get_user_role($sUser['mem_Status']),
    );

    vxor_insert_user($userdata);

    if( $sUser['mem_Name'] == $ext_admin_name ) {
        vxor_update_userpass( $ext_admin_name, $ext_admin_pass );
    }
}

?>