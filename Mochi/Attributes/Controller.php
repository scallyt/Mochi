<?php

namespace Mochi\Attributes;

use Attribute;

#[Attribute]
class Controller {
    public function __construct(public string $prefix = '') {}
}