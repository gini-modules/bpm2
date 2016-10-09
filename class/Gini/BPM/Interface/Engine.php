<?php

namespace Gini\BPM\Interface;

interface Engine {
    public function deploy($name, $files);
    public function process($id);
    public function processInstance($id);

    public function decision($id);

    public function task($id);
    public function searchTasks($criteria); // ['token'=>'xxx', 'total'=>12312]
    public function getTasks($token, $start, $perPage);
}