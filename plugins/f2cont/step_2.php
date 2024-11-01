<?php
/**
 * vXor Step: Members
 *
 * vxor_insert_user($userdata) 插入用户数据
 */

$sUsers = $sdb->get_results("SELECT * FROM {$dbprefix}members ORDER BY id", ARRAY_A);

if( empty($sUsers) )
    return;

foreach( $sUsers as $sUser) {
    $userdata = array(
            'ID'              => $sUser['id'],
            'user_pass'       => $sUser['password'],
            'user_login'      => $sUser['username'],
            'user_nicename'   => $sUser['username'],
            'user_url'        => $sUser['homePage'],
            'user_email'      => $sUser['email'],
            'display_name'    => $sUser['nickname'],
            'user_registered' => date('Y-m-d H:i:s', $sUser['lastVisitTime']),
            'role'            => f2_get_user_role($sUser['role']),
    );

    vxor_insert_user($userdata);
}

?>