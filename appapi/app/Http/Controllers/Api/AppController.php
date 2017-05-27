<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Log\DeviceApps;
use App\Model\Log\DeviceInfo;
use App\Model\IosApplicationConfig;
use App\Model\ZyGame;
use App\Jobs\AdtRequest;

class AppController extends Controller
{
    /**
     * 初始化接口（在程序启动时第一时间调用）
     * @api api/app/initialize
     * @apigroup
     * @apiparam device_apps string N json格式用户设备上所安装的app列表，`[{},{}...]`
     * @apiparam device_info string Y json格式用户设备信息 `{"brand":"samsung","model":"SM-A8000","vname":"5.1.1","vcode":22,"imei":"352324076134061","imsi":"460021714867416","number":"","screen":"1080x1920"}`
     * @apiparam app_version string Y app的版本（非version_code），用于检查更新，当最新更新包的版本不等于app_version时则产生更新
     * @apireturn allow_sub_num int 该游戏允许创建的小号数量
     * @apireturn bind_phone array 绑定手机相关设置
     * @apireturn bind_phone.need bool 是否在登陆后提示绑定手机
     * @apireturn bind_phone.interval int 每隔多久弹出一次提示，单位（ms）
     * @apireturn bind_phone.enforce bool 是否强制绑定（不绑定就不能关闭窗口）
     * @apireturn service array 服务相关设置
     * @apireturn service.qq string 客户服务QQ号码
     * @apireturn service.interval int HTTP心跳频率，单位（ms）
     * @apireturn service.af_download string 安锋下载地址（在小号界面显示安锋助手下载）
     * @apireturn service.share string 用户分享页（的链接地址）
     * @apireturn service.phone string 客户服务电话
     * @apireturn service.page string 客户服务页面
     * @apireturn af_login bool 是否启用我们自己的（安锋）登陆
     * @apireturn update array 如果检查到更新则返回更新相关信息
     * @apireturn update.down_url string 更新包下载地址
     * @apireturn update.version string 更新包版本
     * @apireturn update.force_update 是否强制更新
     * @apireturn real_name array 实名制相关设置
     * @apireturn real_name.need bool 是否在登陆后弹出实名制提示
     * @apireturn real_name.enforce bool 是否强制实名制（不实名就不能关闭窗口）
     * @apireturn real_name.pay_need bool 是否在支付时弹出实名制提示
     * @apireturn real_name.pay_enforce bool 是否在支付时强制实名（不实名不能支付）
     * @apireturn protocol array 注册协议
     * @apireturn protocol.url string 注册协议的URL地址（可web打开）
     * @apireturn protocol.title string 注册协议的标题
     * @apireturn oauth_login array 第三方平台登陆设置
     * @apireturn oauth_login.qq array QQ登陆设置
     * @apireturn oauth_login.qq.url array QQ登陆URL页面
     * @apireturn oauth_login.weixin array 微信登陆设置
     * @apireturn oauth_login.weixin.url array 微信登陆URL页面
     * @apireturn oauth_login.weibo array 微博登陆设置
     * @apireturn oauth_login.weibo.url array 微博登陆URL页面
     * @apireturn ios_app_config IOS应用设置，在`_os=1`有效
     * @apireturn ios_app_config.bundle_id 应用包名
     * @apireturn ios_app_config.apple_id 应用ID
     * @apireturn ios_app_config.name 应用名称
     * @apierrorcode Success 成功
     * @apierrorcode Remind 逻辑错误
     * @apierrorcode Error 系统错误
     * {
     *     "allow_sub_num": 1,
     *     "bind_phone": {
     *         "need": true,
     *         "interval": 259200000,
     *         "enforce": false
     *     },
     *     "service": {
     *         "qq": "4000274365",
     *         "interval": 2000,
     *         "af_download": "http://appicdn.anfeng.cn/down/AnFengHelper_lastest.apk",
     *         "share": "http://www.anfeng.cn/app",
     *         "phone": "4000274365",
     *         "page": "http://m.anfeng.cn/service.html"
     *     },
     *     "af_login": false,
     *     "update": {},
     *     "real_name": {
     *         "need": false,
     *         "enforce": false,
     *         "pay_enforce": false,
     *         "pay_need": false
     *     },
     *     "0": "",
     *     "protocol": {
     *         "url": "http://passtest.anfeng.cn/agreement.html",
     *         "title": "安锋用户协议"
     *     },
     *     "oauth_login": {
     *         "qq": {
     *             "url": "http://passtest.anfeng.cn/oauth/login/qq?appid=2&rid=255&device_id=2mv4u46k1h44oksow8ccc8k4o"
     *         },
     *         "weixin": {
     *             "url": "http://passtest.anfeng.cn/oauth/login/weixin?appid=2&rid=255&device_id=2mv4u46k1h44oksow8ccc8k4o"
     *         },
     *         "weibo": {
     *             "url": "http://passtest.anfeng.cn/oauth/login/weibo?appid=2&rid=255&device_id=2mv4u46k1h44oksow8ccc8k4o"
     *         }
     *     },
     *     "ios_app_config": {}
     * }
     */
    public function InitializeAction() {
        $pid = $this->procedure->pid;
        $rid = $this->parameter->tough('_rid');
        $imei = $this->parameter->get('_imei');
        $uuid = $this->parameter->tough('_device_id');
        $apps = $this->parameter->get('device_apps');
        $info = $this->parameter->tough('device_info');
        $app_version = $this->parameter->tough('app_version');
        $os = $this->parameter->get('_os');

        if($apps) {
            $_apps = json_decode($apps, true);
            if($_apps) {
                $device_apps = new DeviceApps;
                $device_apps->imei = $imei;
                $device_apps->uuid = $uuid;
                $device_apps->apps = $_apps;
                $device_apps->asyncSave();
            } else {
                log_error('report_device_apps_parse_error', null, '上报的DeviceApps格式无法解析');
            }
        }

        $_info = json_decode($info, true);
        if($_info) {
            $device_info = new DeviceInfo;
            $device_info->imei = $imei;
            $device_info->uuid = $uuid;
            $device_info->info = $_info;
            $device_info->asyncSave();
        } else {
            log_error('report_device_info_parse_error', null, '上报的DeviceInfo格式无法解析');
        }

        // check update
        $update = new \stdClass;
        $update_apks = $this->procedure->update_apks()->orderBy('dt', 'desc')->first();
        if($update_apks && version_compare($update_apks->version, $app_version, '>')) {
            $update = array(
                'down_url' => $update_apks->down_uri,
                'version' => $update_apks->version,
                'force_update' => env('APP_DEBUG') ? false : $update_apks->force_update,
            );
        }

        $oauth_params = sprintf('appid=%d&rid=%d&device_id=%s', $pid, $rid, $uuid);
        $oauth_qq = env('oauth_url_qq');
        $oauth_qq .= (strpos($oauth_qq, '?') === false ? '?' : '&') . $oauth_params;
        $oauth_weixin = env('oauth_url_weixin');
        $oauth_weixin .= (strpos($oauth_weixin, '?') === false ? '?' : '&') . $oauth_params;
        $oauth_weibo = env('oauth_url_weibo');
        $oauth_weibo .= (strpos($oauth_weibo, '?') === false ? '?' : '&') . $oauth_params;
        
        // ios
        $ios_app_config = new \stdClass();
        if($os == 1) {
        	$game = ZyGame::find($this->procedure->gameCenterId);
        	$application_config = IosApplicationConfig::find($pid);
        	if($application_config) {
        		$ios_app_config = [
        			'bundle_id' => $application_config->bundle_id,
        			'apple_id' => $application_config->apple_id,
        			'name' => $game ? $game->name : '',
        		];
        	}
        }

        dispatch((new AdtRequest(["imei"=>$imei,"gameid"=>$pid,"rid"=>$rid]))->onQueue('adtinit'));

        return [
            'allow_sub_num' => $this->procedure_extend->allow_num,
            'af_login' => ($this->procedure_extend->enable & (1 << 6)) != 0,
            'oauth_login' => [
                'qq' => [
                    'url' => $oauth_qq,
                ],
                'weixin' => [
                    'url' => $oauth_weixin,
                ],
                'weibo' => [
                    'url' => $oauth_weibo,
                ]
            ],
            'protocol' => [
                'title' => env('protocol_title'),
                'url' => env('protocol_url'),
            ],
            'update' => $update,
            'service' => [
                'qq' => $this->procedure_extend->service_qq,
                'page' => $this->procedure_extend->service_page,
                'phone' => $this->procedure_extend->service_phone,
                'share' => $this->procedure_extend->service_share,
                'interval' => max(2000, $this->procedure_extend->heartbeat_interval),
                'af_download' => env('af_download'),
            ],
            'bind_phone' => [
                'need' => ($this->procedure_extend->enable & (1 << 16)) == (1 << 16),
                'enforce' => ($this->procedure_extend->enable & 0x00000030) == 0x00000030,
                'interval' => $this->procedure_extend->bind_phone_interval,
            ],
            'real_name' => [
                'need' => ($this->procedure_extend->enable & 0x00000001) == 0x00000001,
                'enforce' => ($this->procedure_extend->enable & 0x00000003) == 0x00000003,
                'pay_need' => ($this->procedure_extend->enable & 0x00000004) == 0x00000004,
                'pay_enforce' => ($this->procedure_extend->enable & 0x0000000C) == 0x0000000C,
            ],

            'ios_app_config' => $ios_app_config,

            ''
        ];
    }


    /**
     * 应用退出接口
     * @api api/app/logout
     * @apireturn img string 在退出时显示一张广告图（的URL地址）
     * @apireturn type
     * @apireturn redirect string 打开图片时跳转到URL地址
     * @apireturn inside bool 是否在外部打开URL(redirect)地址
     */
    public function LogoutAction() {
        return [
            'img' => $this->procedure_extend->logout_img,
            'type' => $this->procedure_extend->logout_type,
            'redirect' => $this->procedure_extend->logout_redirect,
            'inside' => $this->procedure_extend->logout_inside,
        ];
    }

    public function VerifySMSAction() {
        $mobile = $this->parameter->tough('mobile', 'mobile');
        $code = $this->parameter->tough('code', 'smscode');

        if(!verify_sms($mobile, $code)) {
            throw new ApiException(ApiException::Remind, trans('messages.invalid_smscode'));
        }

        return ['result' => true];
    }

    /**
     * 获取一个UUID
     * @apireturn uuid string 一个24位或25位的唯一（该接口返回的UUID永不重复）字符串
     */
    public function UuidAction() {
        return ['uuid' => uuid()];
    }

    public function HotupdateAction() {
        $pid = $this->procedure->pid;
        $sdk_version  = $this->parameter->tough('sdk_version');

        if(in_array($pid, [1452, 1533, 1530])) {
            $manifest = [];
            $manifest['version'] = '1.0.0';
            $manifest['bundles'][] = ['type' => 'lib', 'pkg' => 'com.anfeng.pay'];

            $updates = [];
            $updates['pkg'] = 'com.anfeng.pay';
            $updates['version'] = 403;
            $updates['use_version'] = 403; // 回退版本，默认与version一致
            $updates['url'] = 'http://afsdkhot.qcwan.com/anfeng/down/com.anfeng.pay403.apk';
        } else {
            $manifest = [];
            $manifest['version'] = '1.0.0';
            $manifest['bundles'][] = ['type'=>'lib','pkg'=>'com.anfeng.pay'];

            $updates = [];
            $updates['pkg'] = 'com.anfeng.pay';
            $updates['version'] = 40;
            $updates['use_version'] = 40; // 回退版本，默认与version一致
            $updates['url'] = 'http://afsdkup.qcwan.com/down/com.anfeng.pay.apk';
        }

        return ['manifest'=>$manifest, 'updates'=>[$updates]];
    }
}
