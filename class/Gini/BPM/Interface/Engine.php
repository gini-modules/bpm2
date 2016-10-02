<?php

namespace Gini\BPM\Interface;

interface Engine {
    public function deploy($name, $files);
    public function process($id);
    public function processInstance($id);
    public function decision($id);
}