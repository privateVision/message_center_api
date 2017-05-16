<?php
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

d('./app/', function($file) use(&$data) {
    
    $content = file_get_contents($file);
    if(!$content) return;
    
    $re = preg_match_all('/throw\s+new\s+ApiException.*?,\s*(.*)\)\s*;\s*\/\/\s*LANG\s*:\s*(\w+)/', $content, $result);
    if(!$re) return;

    foreach($result[1] as $k => $v) {
        $data[$result[2][$k]] = $v;
        $content = str_replace($v, $result[2][$k], $content);
    }
    
    $re = preg_match_all('/throw\s+new\s+Exception\s*\((.*?),.*?;\s*\/\/\s*LANG\s*:\s*(\w+)/', $content, $result);
    if(!$re) return;

    foreach($result[1] as $k => $v) {
        $data[$result[2][$k]] = $v;
        $content = str_replace($v, $result[2][$k], $content);
    }
    
    file_put_contents($file, $content);
});

foreach($data as $k => $v) {
    echo sprintf("'%s'%s => %s,\n", $k, str_pad('', 40-strlen($k), ' ', STR_PAD_RIGHT), $v);
}