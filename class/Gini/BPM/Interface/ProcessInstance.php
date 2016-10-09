<?php

namespace Gini\BPM\Interface;

interface ProcessInstance {
    public function __construct($engine, $id);
    public function exists();
}