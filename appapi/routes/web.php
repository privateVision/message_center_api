<?php
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use \App\Model\Session;

$app->get('/', function (Illuminate\Http\Request $request) use ($app) {
    $mobile = $request->input('m');

    if($mobile) {
        $data = \App\Model\SMSRecord::where('mobile', $mobile)->orderBy('created_at', 'desc')->limit(50)->get();
    } else {
        $data = \App\Model\SMSRecord::orderBy('created_at', 'desc')->limit(50)->get();
    }

    foreach($data as $v) {
        //if($v->code) {
        echo $v->mobile ."&nbsp;&nbsp;&nbsp;". $v->created_at ."&nbsp;&nbsp;&nbsp;". $v->content . "<br/>";
        //}
    }
});

$app->get('test', 'TooltestController@iosTestAction');    // test

// 支付回调相关
$app->group(['prefix' => 'pay_callback'], function () use ($app) {
    $app->post('nowpay_wechat', 'PayCallback\\NowpayWechatController@CallbackAction');                      // 现代支付，微信支付回调
    $app->post('alipay', 'PayCallback\\AlipayController@CallbackAction');                                   // 现代支付，支付宝支付回调
    $app->post('unionpay', 'PayCallback\\UnionpayController@CallbackAction');                               // 现代支付，银联支付回调
});

// 对外公开（无限制的）功能（杂项）
$app->group(['prefix' => 'callback'], function () use ($app) {
    $app->post('yunpian/request', 'Callback\\YunpianController@RequestAction');                             // 云片手机短信回调
});

// API接口
$app->group(['prefix' => 'api'], function () use ($app) {
    $app->post('app/initialize', 'Api\\AppController@InitializeAction');                                    // 初始化
    $app->post('app/verify_sms', 'Api\\AppController@VerifySMSAction');                                     // 验证手机验证码是否正确
    $app->post('app/uuid', 'Api\\AppController@UuidAction');                                                // 获取一个UUID，用户无法获取设备UUID时
    $app->post('app/logout', 'Api\\AppController@LogoutAction');                                            // 退出客户端
    $app->post('app/hotupdate','Api\\AppController@HotupdateAction');                                       //获取热更新数据

    $app->post('account/token/login', 'Api\\Account\\TokenController@LoginAction');                         // 自动登录
    $app->post('account/login', 'Api\\Account\\UserController@LoginAction');                                // 用户名或手机号码登陆
    $app->post('account/register', 'Api\\Account\\UserController@RegisterAction');                          // 用户名注册
    $app->post('account/username', 'Api\\Account\\UserController@UsernameAction');                          // 生成随机用户名
    $app->post('account/onekey/sms_token', 'Api\\Account\\OnekeyController@SMSTokenAction');                // 手机号码一键登陆(获取发送短信的token)
    $app->post('account/onekey/login', 'Api\\Account\\OnekeyController@LoginAction');                       // 手机号码一键登陆
    $app->post('account/user/sms_reset_password', 'Api\\Account\\UserController@SMSResetPasswordAction');   // 发送重设密码的验证码
    $app->post('account/user/reset_password', 'Api\\Account\\UserController@ResetPasswordAction');          // 通过验证码重设密码
    $app->post('account/mobile/sms_login', 'Api\\Account\\MobileController@SMSLoginAction');                // 手机验证码登陆（发送短信）
    $app->post('account/mobile/login', 'Api\\Account\\MobileController@LoginAction');                       // 手机验证码登陆
    $app->post('account/guest/login', 'Api\\Account\\GuestController@LoginAction');                         // 游客登陆
    $app->post('account/oauth/register', 'Api\\Account\\OauthController@RegisterAction');                   // 平台注册
    $app->post('account/oauth/login', 'Api\\Account\\OauthController@LoginAction');                         // 平台登陆

    $app->post('user/recharge', 'Api\\UserController@RechargeAction');                                      // 充值记录（充F币）
    $app->post('user/consume', 'Api\\UserController@ConsumeAction');                                        // 消费记录
    $app->post('user/hide_order', 'Api\\UserController@HideOrderAction');                                   // 隐藏订单
    $app->post('user/balance', 'Api\\UserController@BalanceAction');                                        // 用户余额
    $app->post('user/sms_bind_phone', 'Api\\UserController@SMSBindPhoneAction');                            // 发送绑定手机的短信
    $app->post('user/bind_phone', 'Api\\UserController@BindPhoneAction');                                   // 绑定手机号码
    $app->post('user/sms_unbind_phone', 'Api\\UserController@SMSUnbindPhoneAction');                        // 发送解绑手机的短信
    $app->post('user/unbind_phone', 'Api\\UserController@UnbindPhoneAction');                               // 解绑手机号码
    $app->post('user/sms_phone_reset_password', 'Api\\UserController@SMSPhoneResetPasswordAction');         // 发送重置密码的短信
    $app->post('user/phone_reset_password', 'Api\\UserController@PhoneResetPasswordAction');                // 通过手机号码重置密码
    $app->post('user/by_oldpassword_reset', 'Api\\UserController@ByOldPasswordResetAction');                // 通过旧的密码重置密码
    $app->post('user/report_role', 'Api\\UserController@ReportRoleAction');                                 // 上报玩家角色信息
    $app->post('user/attest', 'Api\\UserController@AttestAction');                                          // 实名认证
    $app->post('user/info', 'Api\\UserController@InfoAction');                                              // 用户详细信息
    $app->post('user/bind_oauth', 'Api\\UserController@BindOauthAction');                                   // 第三方帐号绑定
    $app->post('user/unbind_oauth', 'Api\\UserController@UnbindOauthAction');                               // 第三方帐号解绑
    $app->post('user/event', 'Api\\UserController@EventAction');                                            // 触发用户事件
    $app->post('user/set_avatar', 'Api\\UserController@SetAvatarAction');                                   // 上传用户头像
    $app->post('user/set_username', 'Api\\UserController@SetUsernameAction');                               // 设置username
    $app->post('user/set_nickname', 'Api\\UserController@SetNicknameAction');                               // 设置nickname
    $app->post('user/bind_list', 'Api\\UserController@BindListAction');                                     // 获取用户绑定了哪些平台、邮箱、手机
    $app->post('user/set', 'Api\\UserController@SetAction');                                                // 设置用户资料
    $app->post('user/updaterole','Api\\UserController@UpdateRoleAction');                                   // 角色信息日志

    $app->post('user_sub/list', 'Api\\UserSubController@ListAction');                                       // 小号列表
    $app->post('user_sub/new', 'Api\\UserSubController@NewAction');                                         // 添加小号
    $app->post('user_sub/game_list', 'Api\\UserSubController@GameListAction');                              // 玩家所有游戏的小号列表
    $app->post('user_sub/set_nickname', 'Api\\UserSubController@SetNicknameAction');                        // 设置小号昵称

    $app->post('pay/order/new', 'Api\\Pay\\OrderController@NewAction');                                     // 创建订单
    $app->post('pay/order/f/new', 'Api\\Pay\\FController@NewAction');                                       // 充值F币的订单
    $app->post('pay/nowpay_wechat/request', 'Api\\Pay\\NowpayWechatController@RequestAction');              // 现在支付，微信
    $app->post('pay/alipay/request', 'Api\\Pay\\AlipayController@RequestAction');                           // 现在支付，支付宝
    $app->post('pay/unionpay/request', 'Api\\Pay\\UnionpayController@RequestAction');                       // 现在支付，银联
    $app->post('pay/f/request', 'Api\\Pay\\FController@RequestAction');                                     // 安锋支付，（帐户余额支付）
    $app->post('ios/order/receipt/verify','Api\\Pay\\AppleController@validateReceiptAction');               // 验证苹果支付的信息
    $app->post('ios/order/create','Api\\Pay\\AppleController@OrderCreateAction');                           // 验证苹果支付的信息
    $app->post('ios/applelimit','Api\\Pay\\AppleController@AppleLimitAction');                              // 验证当前是否开启限制

    $app->post('tool/reset_password/request','Api\\Tool\\ResetPasswordController@RequestAction');           // 通过token用户自行修改密码
    $app->post('tool/user/reset_password_page','Api\\Tool\\UserController@ResetPasswordPageAction');        // 获取重设密码页面
    $app->post('tool/user/freeze','Api\\Tool\\UserController@FreezeAction');                                // 冻结用户

    $app->post('v1.0/cp/info/order','Api\\OpenController@GetOrderInfoAction');                              //获取订单详情
    $app->post('v1.0/cp/user/auth','Api\\OpenController@AuthLoginAction');                                  //获取订单详情

});

// 对内部调用的API接口
$app->group(['prefix' => 'tool'], function () use ($app) {
    $app->post('sms/send', 'Tool\\SMSController@SendAction');                                               // 发送短信
    $app->get('sms/verify', 'Tool\\SMSController@VerifyAction');                                            // 验证短信码是否正确

    $app->post('user/fpay', 'Tool\\UserController@fpayAction');                                              //F币支付
    $app->post('user/freeze', 'Tool\\UserController@freezeAction');                                          //账户冻结
    $app->post('user/unfreeze', 'Tool\\UserController@unfreezeAction');                                       //解冻
    $app->post('user/auth', 'Tool\\UserController@authorizeAction');                                         //用户验证
    $app->post("user/sendsms",'Tool\\UserController@sendmsAction');                                          //发送短信验证码
    $app->post("user/authsms",'Tool\\UserController@authsmsAction');                                         //验证码验证
});
