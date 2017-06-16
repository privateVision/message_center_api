<?php /*
function getCity($ip) {
    $data = ['address' => '', 'city' => '', 'code' => ''];

    $ip2location = new \Naux\IpLocation\IpLocation(app()->databasePath('qqwry.dat'));
    $res2 = $ip2location->getlocation($ip);

    $data['address'] = trim(@$res2['country'] .' '. @$res2['area']);
    $data['city'] = trim($res2['country']);

    $w = function($address) {
        //$a = $b = $c;
        for($i = 0; $i < mb_strlen($address); $i++) {

        }
    }

    $res1 = null;

    if(!$data['address'] || !$data['city']) {
        $res1 = \App\IPIP::find($ip);

        if(!$data['address']) {
            $data['address'] = trim(implode(' ', $res1));
        }

        if(!$data['city']) {
            $data['city'] = isset($res1[2]) ? trim(@$res1[2]) : trim(@$res1[1]);
        }
    }

    if($data['city']) {
        $area = \App\Model\Area::where('name', 'like', "%{$data['city']}%")->first();
        if($area) {
            $data['code'] = $area->id;
        }
    }

    log_debug('ip2location', ['res1' => $res1, 'res2' => $res2, 'data' => $data], $ip);

    return $data;
}*/

Route::any('/test', function() {

});

Route::group(['prefix' => 'web'], function () {
    Route::get('test', 'Web\\TestController@indexAction');
    Route::any('login', 'Web\\TestController@loginAction');
    Route::any('sign', 'Web\\TestController@signAction');
});