<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Parameter;
use App\Model\Log\DeviceApps;
use App\Model\Log\DeviceInfo;
use App\Model\IosApplicationConfig;
use App\Model\ZyGame;

class AppController extends Controller
{
    public function InitializeAction() {
        $pid = $this->parameter->tough('_appid');
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

        return [
            'allow_sub_num' => $this->procedure_extend->allow_num,
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
                'interval' => 86400000,//max(2000, $this->procedure_extend->heartbeat_interval),
                'af_download' => env('af_download'),
            ],
            'bind_phone' => [
                'need' => ($this->procedure_extend->enable & 0x00000010) == 0x00000010,
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
        ];
    }

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
            throw new ApiException(ApiException::Remind, "验证码不正确，或已过期");
        }

        return ['result' => true];
    }

    public function UuidAction() {
        return ['uuid' => uuid()];
    }
/*
    public function ReportAppsAction() {
        $imei = $this->parameter->tough('imei');
        $uuid = $this->parameter->tough('_device_id');
        $apps = $this->parameter->tough('apps');

        $device_apps = new DeviceApps;
        $device_apps->imei = $imei;
        $device_apps->uuid = $uuid;
        $device_apps->apps = $apps;
        $device_apps->save();

        return ['result' => true];
    }

    public function ReportDeviceInfoAction() {
        $imei = $this->parameter->tough('imei');
        $uuid = $this->parameter->tough('_device_id');

        $device_info = new DeviceInfo;
        $device_info->imei = $imei;
        $device_info->uuid = $uuid;
        $device_info->save();

        return ['result' => true];
    }
*/
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
