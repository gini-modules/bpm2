<?php

namespace Gini\BPM\Driver;

interface Engine {
    public function deploy($name, $files);
    public function fetchProcessInstance($processName, $instancID);
    public function startProcessInstance($processName, $data, $tag);
    public function process($id);
    public function processInstance($id);

    public function decision($id);

    public function task($id);
    public function searchTasks(array $criteria); // ['token'=>'xxx', 'total'=>12312]
    public function getTasks($token, $start, $perPage);
}
