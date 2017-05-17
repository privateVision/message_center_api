<?php
$lang = [
    '\'账号已被冻结，无法登录\'' => ['freeze_onlogin',],
    '"角色不存在，无法登录"' => ['role_not_exists',],
    '\'角色已被冻结，无法登录\'' => ['role_freeze_onlogin',],
    '\'未注册第三方账号，请注册\'' => ['3th_not_register',],
    '\'账号被冻结，无法登录\'' => ['freeze',],
    '\'会话失效，请重新登录\'' => ['invalid_token',],
    '\'会话失效，请重新登录\'' => ['invalid_token',],
    '\'会话失效，请重新登录\'' => ['invalid_token',],
    '"验证码不正确，或已过期"' => ['invalid_smscode',],
    '"unionid不允许为空"' => ['unionid_empty',],
    '\'未知的第三方登录类型，type=\'.$type' => ['3th_unknow','未知的第三方登录类型，:type','[\'type\' => $type]'],
    '"unionid不允许为空"' => ['unionid_empty',],
    '\'未知的第三方登录类型，type=\'.$type' => ['3th_unknow','未知的第三方登录类型，:type','[\'type\' => $type]'],
    '"尚未注册"' => ['not_register',],
    '\'服务器等待收到短信...\'' => ['not_accept_sms',],
    '"错误次数太多，请稍后再试"' => ['login_error',],
    '"用户名或者密码不正确"' => ['login_fail',],
    '"用户已注册，请直接登录"' => ['already_register',],
    '\'手机号码尚未绑定\'' => ['mobile_not_bind',],
    '"验证码不正确，或已过期"' => ['invalid_smscode',],
    '\'手机号码尚未绑定\'' => ['mobile_not_bind',],
    '"token失效"' => ['invalid_token',],
    '\'订单不存在\'' => ['order_not_exists',],
    '\'"_appid" not exists:\' . $_appid' => ['invalid_appid', '"_appid" not exists: :appid', '[\'appid\' => $_appid]'],
    '"签名验证失败"' => ['sign_error',],
    '\'不能使用余额或优惠券直接抵扣\'' => ['order_not_use_f',],
    '\'订单不存在\'' => ['order_not_exists',],
    '\'订单已支付完成，请勿重复支付\'' => ['order_already_success',],
    '\'优惠券不可使用，已过期\'' => ['coupon_expire',],
    '\'优惠券不可使用，已过期\'' => ['coupon_expire',],
    '\'银联支付请求失败\'' => ['unionpay_fail',],
    '\'银联支付请求失败 \' . $resdata[\'respMsg\']' => ['unionpay_fail_1','银联支付请求失败 :respMsg','[\'respMsg\' => $resdata[\'respMsg\']]'],
    '\'发起支付失败\'' => ['pay_fail',],
    '\'发起支付失败\'' => ['pay_fail',],
    '\'发起支付失败（\'.$responseData[\'return_msg\'].\'）\'' => ['pay_fail_1','发起支付失败（:return_msg）','[\'return_msg）\' => $responseData[\'return_msg\']]'],
    '\'帐号未实名制，无法支付，请先实名后再操作\'' => ['check_in_before_pay',],
    '"未找到订单"' => ['order_not_exists',],
    '"未找到相关的商品"' => ['product_not_exists',],
    '"请填写正确的通知地址"' => ['notifyurl_error',],
    '"bundle_id 或iap 不存在"' => ['bundle_ipa_not_exists',],
    '\'计费点不存在\'' => ['product_not_exists',],
    '\'帐号未实名制，无法支付，请先实名后再操作\'' => ['check_in_before_pay',],
    '\'计费点不存在\'' => ['product_not_exists',],
    '\'帐号未实名制，无法支付，请先实名后再操作\'' => ['check_in_before_pay',],
    '\'MyCard 支付请求失败\'' => ['mycard_request_fail',],
    '"修改失败，页面已失效"' => ['reset_password_invalid_page',],
    '"修改失败，页面已失效"' => ['reset_password_invalid_page',],
    '"修改失败，页面已失效"' => ['reset_password_invalid_page',],
    '"用户不存在"' => ['user_not_exists',],
    '\'用户不存在\'' => ['user_not_exists',],
    '"修改失败，昵称已经存在"' => ['nickname_not_exists',],
    '"修改失败，小号不存在"' => ['modify_usersub_not_exists',],
    '"小号创建数量已达上限"' => ['usersub_much',],
    '\'请先登录\'' => ['invalid_token',],
    '\'会话已失效，请重新登录\'' => ['invalid_token',],
    '\'会话已失效，请重新登录\'' => ['invalid_token',],
    '\'会话已失效，请重新登录\'' => ['invalid_token',],
    '\'账号已被冻结\'' => ['freeze',],
    '\'"_appid" not exists:\' . $_appid' => ['invalid_appid', '"_appid" not exists: :appid', '[\'appid\' => $_appid]'],
    '"签名验证失败"' => ['sign_error',],
    '"__appid not found:{$_appid}"' => ['invalid_appid', '"_appid" not exists: :appid', '[\'appid\' => $_appid]'],
    '"生日格式不正确，yyyymmdd"' => ['birthday_format_error',],
    '"旧的密码不正确"' => ['oldpassword_error',],
    '"手机号码已经绑定了其它账号"' => ['mobile_bind_other',],
    '"该账号已经绑定了这个手机号码"' => ['mobile_already_bind',],
    '"该功能已停用"' => ['func_disable',],
    '"绑定失败，验证码不正确，或已过期"' => ['invalid_smscode',],
    '"该账号已经绑定了手机号码"' => ['user_already_bind_mobile',],
    '"手机号码已经绑定了其它账号"' => ['mobile_bind_other',],
    '"还未绑定手机号码，无法解绑"' => ['not_bind_onunbind',],
    '"手机号码解绑失败，验证码不正确，或已过期"' => ['invalid_smscode',],
    '"您必需重设您的用户名才能解绑"' => ['reset_username_onunbind',],
    '"解绑失败，用户名已被占用"' => ['username_exists_onbind',],
    '"还未绑定手机号码，无法使用该方式重置密码"' => ['not_reset_password_unbind_mobile',],
    '"还未绑定手机号码，无法使用该方式重置密码"' => ['not_reset_password_unbind_mobile',],
    '"验证码不正确，或已过期"' => ['invalid_smscode',],
    '"身份证号码不正确"' => ['cardno_error',],
    '"unionid不允许为空"' => ['unionid_empty',],
    '"账号已经绑定了" . config("common.oauth.{$type}.text", \'第三方\')' => ['3th_already_bind','账号已经绑定了:type', '[\'type\' => config("common.oauth.{$type}.text")]'],
    'config("common.oauth.{$type}.text", \'第三方\') . "已经绑定了其它账号"' => ['3th_already_bind_other',':type已经绑定了其它账号', '[\'type\' => config("common.oauth.{$type}.text")]'],
    '"为了防止遗忘账号，请绑定手机或者其他社交账号后再解除绑定"' => ['3th_unbind_error',],
    '\'头像上传失败：\' . $e->getMessage()' => ['avatar_set_error','头像上传失败：:eMsg', '[\'eMsg\' => $e->getMessage()]'],
    '\'设置失败，用户名已被占用\'' => ['username_exists_onset',],
    '"验证码不正确，或已过期"' => ['invalid_smscode',],
    '"参数\"{$key}\"格式不正确"' => ['param_format_error','参数":key"格式不正确', '[\'key\' => $key]'],
    '\'param is missing:"\'.$key.\'"\'' => ['param_missing','param is missing: :key', '[\'key\' => $key]'],
    '"参数\"{$key}\"格式不正确"' => ['param_format_error','参数":key"格式不正确', '[\'key\' => $key]'],
    '"\"{$mobile}\" 不是一个有效的手机号码"' => ['mobile_format_error',':mobile不是一个有效的手机号码', '[\'mobile\' => $mobile]'],
    '"用户名错误，不能为纯数字"' => ['useranme_format_error_d',],
    '"用户名长度在6-15位之间"' => ['useranme_format_error_l',],
    '"用户名只能由数字和字母组成"' => ['useranme_format_error_dw',],
    '"验证码错误"' => ['invalid_smscode',],
    '"\"{$url}\" url错误"' => ['url_format_error',':url url错误','[\'url\' => $url]'],
    '"密码不能为空"' => ['password_empty',],
    '"昵称长度不能超过14个字符，1个汉字算2个"' => ['nickname_format_error_l',],
    '"昵称长度不能超过10个字符，1个汉字算2个"' => ['subnickname_format_error_l',],
];
/*
function d($dir, $cb){
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if($file == '.' || $file == '..') continue;

                $path = $dir . $file;
                if(is_file($path)) {
                    $cb($path);
                } else {
                    d($path . '/', $cb);
                }
            }

            closedir($dh);
        }
    }
}

$data = [];

d('./app/', function($file) use(&$data, &$lang) {
    
    $content = file_get_contents($file);
    if(!$content) return;

    $is_match = false;

    $re = preg_match_all('/throw\s+new\s+ApiException.*?,\s*(.*)\)\s*;/', $content, $result);
    if($re) {
        foreach($result[1] as $k => $v) {
            if(isset($lang[$v])) {
                if(isset($lang[$v][2])) {
                    $resplace = "trans('messages.{$lang[$v][0]}', {$lang[$v][2]})";
                } else {
                    $resplace = "trans('messages.{$lang[$v][0]}')";
                }
                $content = str_replace($v, $resplace, $content);
            }
        }
        
        file_put_contents($file, $content);
    }
    
    $re = preg_match_all('/throw\s+new\s+Exception\s*\((.*?),.*?;/', $content, $result);
    if($re) {
        foreach($result[1] as $k => $v) {
            if(isset($lang[$v])) {
                if(isset($lang[$v][2])) {
                    $resplace = "trans('messages.{$lang[$v][0]}', {$lang[$v][2]})";
                } else {
                    $resplace = "trans('messages.{$lang[$v][0]}')";
                }
                $content = str_replace($v, $resplace, $content);
            }
        }
        
        file_put_contents($file, $content);
    }
});
*/
foreach($lang as $k => $v) {
    $key = "'{$v[0]}'";
    if(isset($v[1])) {
        echo str_pad($key, 40, ' ', STR_PAD_RIGHT)."=> '{$v[1]}',";
    } else {
        echo str_pad($key, 40, ' ', STR_PAD_RIGHT)."=> '{$k}',";
    }

    echo "\n";
}