<?php

namespace Gini\Controller\CLI\BPM;

trait Base {

    private function getOpt(array $args) {
        $opt = [];
        foreach ($args as $kv) {
            $kv_arr =  explode(':=', $kv, 2);
            if (count($kv_arr)==1) {
                $kv_arr =  explode('=', $kv, 2);
                list($k, $v)=array_map('trim', $kv_arr);
            } else {
                $k = trim($kv_arr[0]);
                $v = @json_decode($kv_arr[1], true);
            }
            if (!$v) {
                $opt['_'][] = $k;
            } else {
                $opt[$k] = $v;
            }
        }
        return $opt;
    }

    private function getEngine(array $opt) {
        $opt['bpm'] or die("You need to specify BPM name!\n");
        return \Gini\BPM\Engine::of($opt['bpm']);
    }

}
