<?php

namespace Gini\BPM\Driver;

interface User {
    public function delete();
    public function update(array $vars);
    public function updatePassWord(array $vars);
}
