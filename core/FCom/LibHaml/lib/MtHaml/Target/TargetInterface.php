<?php defined('BUCKYBALL_ROOT_DIR') || die();

namespace MtHaml\Target;

use MtHaml\Environment;
use MtHaml\Node\NodeAbstract;

interface TargetInterface
{
    function parse(Environment $env, $string, $filename);
    function compile(Environment $env, NodeAbstract $node);
}

