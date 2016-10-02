<?php

namespace Gini\BPM\Interface;

interface Task {
    public function setAssignee($userId);
    public function submitForm(array $vars);
    public function submitForm(array $vars);
    public function complete();
}