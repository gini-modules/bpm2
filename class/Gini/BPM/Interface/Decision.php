<?php

namespace Gini\BPM\Interface;

interface Decision {
    function evaluate(array $vars);
}