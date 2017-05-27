<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Model\IpRefused;
use App\Exceptions\Exception;
use App\Parameter;
use App\Model\Procedures;
use App\Model\ProceduresExtend;
use Illuminate\Http\Response;
use App\Redis;

class Controller extends \App\Controller
{
    protected $procedure = null;
    protected $procedure_extend = null;

    protected $request = null;
    protected $parameter = null;

    public function onError(Request $request, \Exception $e)
    {
        if ($e instanceof ApiException) {
            log_warning('ApiException', ['code' => $e->getCode(), 'path' => $request->path(), 'reqdata' => $request->all()], $e->getMessage());
            $content = ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'data' => $e->getData()];
        } elseif ($e instanceof Exception) {
            log_warning('Exception', ['code' => $e->getCode(), 'path' => $request->path(), 'reqdata' => $request->all()], $e->getMessage());
            $content = ['code' => ApiException::Remind, 'msg' => $e->getMessage(), 'data' => null];
        } else {
            log_error('error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'path' => $request->path(),
                'reqdata' => $request->all()
            ]);

            $content = ['code' => ApiException::Error, 'msg' => 'system error', 'data' => null];
        }

        return response($content, 200);
    }

    public function onResponse(Request $request, Response $response)
    {
        $content = [
            'code' => ApiException::Success,
            'msg' => null,
            'data' => $response->getOriginalContent()
        ];

        $response->setContent($content);

        return $response;
    }

    public function before(Request $request)
    {
        parent::before($request);

        self::checkDevice($request);

        // 封ip
        $data = IpRefused::where('ip', getClientIp())->first();
        if ($data) {
            throw new ApiException(ApiException::Error, trans('messages.ipfreeze'));
        }

        $data = array_map(function ($v) {
            return strval($v);
        }, $request->all());

        $this->parameter = new Parameter($data);
        $_appid = $this->parameter->tough('_appid');
        $_sign = $this->parameter->tough('_sign');

        $this->procedure = Procedures::from_cache($_appid);
        if (!$this->procedure) {
            throw new ApiException(ApiException::Error, trans('messages.invalid_appid', ['appid' => $_appid]));
        }

        $appkey = $this->procedure->appkey();

        unset($data['_sign']);
        ksort($data);
        $sign = md5(http_build_query($data) . '&key=' . $appkey);

        if ($_sign !== $sign) {
            log_error('sign_error', ['str' => http_build_query($data) . '&key=' . $appkey], trans('messages.sign_error'));
            throw new ApiException(ApiException::Error, trans('messages.sign_error'));
        }

        // --------- 平台登录特殊处理 ---------

        $__appid = $this->parameter->get('__appid');
        if ($__appid) {
            $this->procedure = Procedures::from_cache($__appid);
            if (!$this->procedure) {
                throw new ApiException(ApiException::Error, trans('messages.invalid_appid', ['appid' => $_appid]));
            }

            $this->parameter->set('_appid', $__appid);
        }

        $__rid = $this->parameter->get('__rid');
        if ($__rid) {
            $this->parameter->set('_rid', $__rid);
        }

        // -------- procedures_extend --------

        $this->procedure_extend = ProceduresExtend::find($this->procedure->pid);
        if (!$this->procedure_extend) {
            $this->procedure_extend = new ProceduresExtend;
            $this->procedure_extend->pid = $_appid;
            $this->procedure_extend->service_qq = env('service_qq');
            $this->procedure_extend->service_page = env('service_page');
            $this->procedure_extend->service_phone = env('service_phone');
            $this->procedure_extend->service_share = env('service_share');
            $this->procedure_extend->heartbeat_data_refresh = 60000;
            $this->procedure_extend->heartbeat_interval = 2000;
            $this->procedure_extend->enable = 0x00000010 | 0x00000002; // 绑定手机（不强制）、实名（不强制）
            $this->procedure_extend->bind_phone_interval = 259200000;
            $this->procedure_extend->logout_img = env('logout_img');
            $this->procedure_extend->logout_redirect = env('logout_redirect');
            $this->procedure_extend->logout_inside = true;
            $this->procedure_extend->allow_num = 1;
            $this->procedure_extend->create_time = time();
            $this->procedure_extend->update_time = time();
            $this->procedure_extend->save();
        }

        // --------------- end ---------------

        $this->request = $request;
    }

    public function checkDevice($request)
    {
        $config = [
            [
                'method' => 'RegisterAction',   //过滤方法
                'expire' => 6000,                 //持续时间（豪秒）
                'times' => 10,                  //次数
                'msg' => 'reg_limit',           //描述
                'status'=>'normal',               //状态，'normal':开启验证，'hidden':关闭验证
            ],
            [
                'method' => 'LoginAction',
                'expire' => 6000,
                'times' => 10,
                'msg' => 'login_limit',
                'status'=>'normal'
            ]
        ];

        $methodName = $request->route()->getActionMethod();

        foreach ($config as $k => $v) {
            if ($v['method'] == $methodName) {

                if($v['status']=='close')return;

                $expire = $v['expire'];
                $times = $v['times'];
                $msg = $v['msg'];

                $ip = getClientIp();

                $key = md5($methodName . '_' . $ip);

                $value = Redis::get($key);

                if (!$value) {
                    Redis::set($key, serialize(['times' => 1, 'expire' => $expire]), 'PX', $expire);
                } else {

                    $value = unserialize($value);

                    if ($value['times'] >= $times) {

                        if(IpRefused::where('ip', $ip)->first())return;

                        $ipRefused = new IpRefused();
                        $ipRefused->ip = $ip;
                        $ipRefused->time = time();
                        $ipRefused->method = $methodName;
                        $ipRefused->save();

                        return;

//                        throw new ApiException(ApiException::Remind, trans('messages.'.$msg));
                    }

                    $value['times'] = $value['times'] + 1;

                    $value['expire'] = $expire = Redis::ttl($key);

                    Redis::set($key, serialize($value), 'EX', $expire);
                }

                break;
            }
        }

    }
}