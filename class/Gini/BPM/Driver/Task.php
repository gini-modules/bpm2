<?php

namespace Gini\BPM\Driver;

interface Task {
    public function setAssignee($userId);
    public function submitForm(array $vars);
    public function complete(array $vars);
    public function addComment($message);
    public function getComments();
    public function getVariables($name);
}
