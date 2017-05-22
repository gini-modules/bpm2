<?php

namespace Gini\BPM\Driver;

interface User {
    public function create(array $vars);
    public function delete();
    public function update(array $vars);
    public function changePassword($password, $newpassword);
}
