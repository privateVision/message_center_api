<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Model\Log\DeviceApps;
use App\Model\Log\DeviceInfo;
use App\Model\IosApplicationConfig;
use App\Model\ZyGame;
use App\Jobs\AdtRequest;

class AppController extends Controller
{
    public function InitializeAction() {
        $pid = $this->procedure->pid;
        $rid = $this->parameter->tough('_rid');
        $imei = $this->parameter->get('_imei');
        $uuid = $this->parameter->tough('_device_id');
        $apps = $this->parameter->get('device_apps');
        $info = $this->parameter->tough('device_info');

        $appversion = $this->parameter->get('app_version'); // 4.0
        if(!$appversion) {
            $appversion = $this->parameter->tough('_app_version');// 4.1
        }

        $os = $this->parameter->get('_os');

        // 广告统计，加入另一个队列由其它项目处理
        if($imei) {
            dispatch((new AdtRequest(['imei' => $imei, 'gameid' => $pid, 'rid' => $rid]))->onQueue('adtinit'));
        }

        if($apps) {
            $_apps = json_decode($apps, true);
            if($_apps) {
                $device_apps = new DeviceApps;
                $device_apps->pid = $pid;
                $device_apps->rid = $rid;
                $device_apps->imei = $imei;
                $device_apps->uuid = $uuid; // 兼容字段，以后都使用device_id
                $device_apps->device_id = $uuid;
                $device_apps->version = $this->parameter->get('_version');
                $device_apps->app_version = $this->parameter->get('_app_version');
                $device_apps->apps = $_apps;
                $device_apps->asyncSave();
            } else {
                log_error('report_device_apps_parse_error', null, '上报的DeviceApps格式无法解析');
            }
        }

        $_info = json_decode($info, true);
        if($_info) {
            $device_info = new DeviceInfo;
            $device_info->pid = $pid;
            $device_info->rid = $rid;
            $device_info->imei = $imei;
            $device_info->uuid = $uuid;
            $device_info->device_id = $uuid;
            $device_info->version = $this->parameter->get('_version');
            $device_info->app_version = $this->parameter->get('_app_version');
            $device_info->info = $_info;
            $device_info->asyncSave();
        } else {
            log_error('report_device_info_parse_error', null, '上报的DeviceInfo格式无法解析');
        }

        // 检查更新
        $update = new \stdClass;
        $update_apks = $this->procedure->update_apks()->orderBy('version', 'desc')->first();
        if($update_apks && version_compare($update_apks->version, $appversion, '>')) {
            // 如果设置了此字段，只在符合该IP的用户会更新
            $is_updated = false;
            if($update_apks->test_ip) {
                $clientip = getClientIp();
                $ips = explode(',', $update_apks->test_ip);
                foreach($ips as $ip) {
                    if($ip == $clientip) {
                        $is_updated = true;
                        break;
                    }
                }
            } else {
                $is_updated = true;
            }

            if($is_updated) {
                $update = array(
                    'down_url' => httpsurl($update_apks->down_uri),
                    'version' => $update_apks->version,
                    'force_update' => env('APP_DEBUG') ? false : $update_apks->force_update,
                );
            }
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

        return [
            'allow_sub_num' => $this->procedure_extend->allow_num,
            'af_login' => ($this->procedure_extend->enable & (1 << 6)) != 0,
            'oauth_login' => [
                'qq' => [
                    'url' => httpsurl($oauth_qq),
                ],
                'weixin' => [
                    'url' => httpsurl($oauth_weixin),
                ],
                'weibo' => [
                    'url' => httpsurl($oauth_weibo),
                ]
            ],

            'protocol' => [
                'title' => env('protocol_title'),
                'url' => env('protocol_url'),
            ],

            'update' => $update,

            'service' => [
                'qq' => $this->procedure_extend->service_qq,
                'page' => httpsurl($this->procedure_extend->service_page),
                'phone' => httpsurl($this->procedure_extend->service_phone),
                'share' => httpsurl($this->procedure_extend->service_share),
                'interval' => max(2000, $this->procedure_extend->heartbeat_interval),
                'af_download' => httpsurl(env('af_download')),
            ],

            'bind_phone' => [
                'need' =>       ($this->procedure_extend->enable & (1 << 4)) == (1 << 4),
                'enforce' =>    ($this->procedure_extend->enable & (3 << 4)) == (3 << 4),
                'interval' =>   $this->procedure_extend->bind_phone_interval,
            ],

            'real_name' => [
                'need' =>       ($this->procedure_extend->enable & (1 << 0)) == (1 << 0),
                'enforce' =>    ($this->procedure_extend->enable & (3 << 0)) == (3 << 0),
                'pay_need' =>   ($this->procedure_extend->enable & (1 << 2)) == (1 << 2),
                'pay_enforce' =>($this->procedure_extend->enable & (3 << 2)) == (3 << 2),
            ],

            'ios_app_config' => $ios_app_config,

            'enable_fb' => $this->procedure_extend->isEnableFB(), // 是否禁用F币功能
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
            'img' => httpsurl($this->procedure_extend->logout_img),
            'type' => $this->procedure_extend->logout_type,
            'redirect' => httpsurl($this->procedure_extend->logout_redirect),
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

        $sdkversion = $this->parameter->get('sdk_version'); // 4.0
        if(!$sdkversion) {
            $sdkversion = $this->parameter->tough('_version');// 4.1
        }

        if(in_array($pid, [1452, 1533, 1530])) {
            $manifest = [];
            $manifest['version'] = '1.0.0';
            $manifest['bundles'][] = ['type' => 'lib', 'pkg' => 'com.anfeng.pay'];

            $updates = [];
            $updates['pkg'] = 'com.anfeng.pay';
            $updates['version'] = 403;
            $updates['use_version'] = 403; // 回退版本，默认与version一致
            $updates['url'] = httpsurl('http://afsdkhot.qcwan.com/anfeng/down/com.anfeng.pay403.apk');
        } else {
            $manifest = [];
            $manifest['version'] = '1.0.0';
            $manifest['bundles'][] = ['type'=>'lib','pkg'=>'com.anfeng.pay'];

            $updates = [];
            $updates['pkg'] = 'com.anfeng.pay';
            $updates['version'] = 40;
            $updates['use_version'] = 40; // 回退版本，默认与version一致
            $updates['url'] = httpsurl('http://afsdkup.qcwan.com/down/com.anfeng.pay.apk');
        }

        return ['manifest'=>$manifest, 'updates'=>[$updates]];
    }
}
