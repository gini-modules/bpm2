<?php

namespace Gini\BPM\Camunda;

class User implements \Gini\BPM\Driver\User
{
    private $camunda;

    public function __construct($camunda, $id, $data=null) {
        $this->camunda = $camunda;
        $this->id = $id;
        $this->_fetchData();
    }

    private function _fetchData() {
        $user = a('user', (int) $this->id);
        if ($user->id) {
            return $user;
        }
        return ;
    }
}

