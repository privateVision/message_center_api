<?php
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| load env setting
|--------------------------------------------------------------------------
|
*/
$env = $app->detectEnvironment(function () {
    $environmentPath = __DIR__ . '/../.env';
    $setEnv = trim(file_get_contents($environmentPath));
    if(!file_exists($environmentPath) || empty($setEnv)) {
        $setEnv = $_SERVER['HTTP_HOST'] == 'sdkv4.qcwan.com'?'online' : ($_SERVER['HTTP_HOST'] == 'sdkv4test.qcwanwan.com'?'testing':'local');
    }
    //兼容.env配置APP_ENV=local或者local两种格式
    $setEnv = str_replace('APP_ENV=', '', $setEnv);
    putenv("APP_ENV=$setEnv");
    if(getenv('APP_ENV') && file_exists(__DIR__ . '/../.' . '.env.' . getenv('APP_ENV'))) {
        Dotenv::load(__DIR__ . '/../', '.' . '.env.' . getenv('APP_ENV'));
    }
});

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
