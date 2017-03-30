<?php

namespace Gini\BPM\Driver;

interface ProcessInstance {
    public function __construct($engine, $id);
    public function exists();
}