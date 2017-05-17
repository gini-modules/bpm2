<?php

namespace Gini\BPM\Driver;

interface Task {
    public function setAssignee($userId);
    public function submitForm(array $vars);
    public function complete(array $vars);
}
