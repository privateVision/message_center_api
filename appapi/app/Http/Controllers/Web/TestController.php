<?php
namespace App\Http\Controllers\Web;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Parameter;

class TestController extends \App\Controller {

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAction(){
        //获取路由原始文件
        $routes = $this->get_routes();

        //获取路由列表
        $rt = \Route::getRoutes();
        $urls = array();
        foreach($rt as $vo){
            $act = $vo->action;
            //print_r($act);exit;
            if($act['prefix'] == '/api') {
                $key = str_replace('api/', '', $vo->uri);
                $urls[] = array(
                    'url'=>$vo->uri,
                    'name'=>isset($routes[$key])?$routes[$key]:'暂无',
                    'controller'=>$act['controller']
                );
            }
        }

        return view('test/index', [
            'urls'=>$urls
        ]);
    }

    /**
     * 登录接口
     */
    public function loginAction(Request $request){
        $data = $request->all();
        if(empty($data['user']) || empty($data['pwd']) || empty($data['appid']) || empty($data['appkey'])){
            return self::response(1, '缺少必要参数');
        }
        $user = $data['user'];
        $pwd = $data['pwd'];
        $appid = $data['appid'];
        $appkey = $data['appkey'];

        //默认初始化数据
        $pubs = array(
            "_appid"=>$appid,
            "_type"=> "json",
            "_timestamp"=> time(),
            "_rid"=> 0,
            "_sign_type"=> "md5",
            "_device_id"=>'460012679802931',
            "_imei"=>'460012679802931',
            "_os"=>0
        );

        //初始化设备
        $inits = array_merge($pubs, array(
                    'app_version'=>'1.0',
                    'device_apps'=>'[
                        {
                            "name":"MD5签名生成器",
                            "pname":"com.sina.weibo.sdk.gensign",
                            "itime":1491389027713,
                            "utime":1491389027713,
                            "vname":"1.0",
                            "vcode":1
                        },
                        {
                            "name":"交管12123",
                            "pname":"com.tmri.app.main",
                            "itime":1488504181245,
                            "utime":1488724951238,
                            "vname":"1.4.0",
                            "vcode":10400
                        }
                    ]',
                    'device_info'=>'{
                            "brand":"Huawei",
                            "model":"H60-L02",
                            "vname":"6.0",
                            "vcode":23,
                            "imei":"864103021832966",
                            "imsi":"460012679802931",
                            "number":"17092671941",
                            "screen":"1080x1812"
                    }'
        ));
        //print_r(self::get_sign($inits, $appkey));
        $res1 = http_curl(url('api/app/initialize'), self::get_sign($inits, $appkey));
        if(!isset($res1['data'])) {
            return self::response(1, '初始化信息失败');
        }

        //登录
        $login = array_merge($pubs, array(
            'username'=>$user,
            'password'=>$pwd
        ));
        $res2 = http_curl(url('api/account/login'), self::get_sign($login, $appkey));
        if(!isset($res1['data'])){
            return self::response(1, '用户登录失败');
        }

        $res2['data']['pubs'] = $pubs;
        return self::response(0, 'success', $res2['data']);
    }

    /**
     * 获取签名
     */
    public function signAction(Request $request){
        $data = $request->all();
        if(empty($data['appid']) || empty($data['appkey']) || empty($data['token']) || empty($data['pubs']) || empty($data['extend'])) {
            return self::response(1, '缺少必要参数');
        }
        $appid = $data['appid'];
        $appkey = $data['appkey'];
        $token = $data['token'];
        $pubs = $data['pubs'];
        $extend = $data['extend'];
        $extend = json_decode($extend, true);

        $params = array_merge($pubs, $extend);
        $params['_token'] = $token;
        ksort($params);

        $res = array(
            'query'=>http_build_query($params) . '&key=' . $appkey,
            'data'=>self::get_sign($params, $appkey)
        );
        return self::response(0, 'success', $res);
    }

    /**
     * @param $params
     * @param $appkey
     * @return mixed
     */
    protected function get_sign($params, $appkey){
        ksort($params);
        $params['_sign'] = md5(http_build_query($params) . '&key=' . $appkey);
        return $params;
    }

    protected function get_routes(){
        $maps = array();
        $filename = base_path('routes/api.php');
        $fh = fopen($filename, 'r');

        while (!feof($fh)) {
            $line = fgets($fh);
            if(!empty($line)) {
                preg_match('/\s*Route.*?\(\s*(\'|")([\d\.\w\/]+)\\1.*?;\s*\/\/\s*(.*)$/', $line, $match);
                if(count($match) == 4){
                    $maps[$match[2]] = $match[3];
                }
            }
        }

        fclose($fh);
        return $maps;
    }

    protected function response($status=0, $msg='success', $data=array()){
        return ['status' => $status, 'msg' => $msg, 'data'=>$data];
    }
}