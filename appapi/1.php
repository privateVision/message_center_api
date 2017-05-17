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

    $is_match = false;
    
    //$re = preg_match_all('/throw\s+new\s+ApiException.*?,\s*(.*)\)\s*;\s*\/\/\s*LANG\s*:\s*(\w+)/', $content, $result);
    $re = preg_match_all('/throw\s+new\s+ApiException.*?,\s*(.*)\)\s*;/', $content, $result);
    if($re) {
        foreach($result[1] as $k => $v) {
            $data[] = $v;

            //$is_match = true;
            //$content = str_replace($v, $result[2][$k], "trans(" . $content .")");
        }
    }
    
    //$re = preg_match_all('/throw\s+new\s+Exception\s*\((.*?),.*?;\s*\/\/\s*LANG\s*:\s*(\w+)/', $content, $result);
    $re = preg_match_all('/throw\s+new\s+Exception\s*\((.*?),.*?;/', $content, $result);
    if($re) {
        foreach($result[1] as $k => $v) {
            $data[] = $v;

            //$is_match = true;
            //$content = str_replace($v, $result[2][$k], "trans(" . $content .")");
        }
    }
    
    if($is_match) {
        //file_put_contents($file, $content);
    }
});

foreach($data as $k => $v) {
    echo sprintf("'' => %s,\n", $v);
}