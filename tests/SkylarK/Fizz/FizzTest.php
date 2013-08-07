<?php
class FizzTest extends PHPUnit_Framework_TestCase
{
   private $_fizz;

   protected function setUp() {
      $this->_fizz = new SkylarK\Fizz\Fizz();
   }
}