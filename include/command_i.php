<?php

interface command_i
{ public function __construct($parameters);
  public function __invoke();
}

