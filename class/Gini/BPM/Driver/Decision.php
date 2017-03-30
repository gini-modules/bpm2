<?php

namespace Gini\BPM\Driver;

interface Decision {
    function evaluate(array $vars);
}