<?php

namespace Gini\BPM;

class Engine {

    public static function of($name) {
        $conf = \Gini\Config::get("bpm.$name");
        $conf['@name'] = $name;
        $driver = $conf['driver']?:'Unknown';
        return \Gini\IoC::construct('\Gini\BPM\\'.$driver.'\Engine', $conf);
    }
}
