<?php

namespace Mochi\Attributes;

use Attribute;

#[Attribute]
class Service {
    public function __construct(public string $prefix = '') {}
}