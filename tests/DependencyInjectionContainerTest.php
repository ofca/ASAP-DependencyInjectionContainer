<?php

use \Ana\DependencyInjectionContainer;

class DependencyInjectionContainerTest extends PHPUnit_Framework_TestCase
{
    public function testParameter()
    {
        $c = new DependencyInjectionContainer();
        $c->setParam('config', 'foo');

        $this->assertEquals($c->getParam('config'), 'foo');        
    }

    public function testRegister()
    {
        $c = new DependencyInjectionContainer();
        $c
            ->setParam('config', '001')
            ->register('cnt', array(
                'class' => '\Ana\DependencyInjectionContainer'
            ))
            ->register('foo', array(
                'class' => 'Foo',
                'arguments' => array('%bar%'),
                'shared'    => true
            ))
            ->register('bar', array(
                'class' => 'Bar',
                'arguments' => array('arg1')
            ))
            ->register('bar2', array(
                'class' => 'Bar',
                'arguments' => array('@config@')
            ))
            ->register('bar3', array(
                'constructor' => function(Foo $foo) {
                    return new Bar($foo);
                },
                'arguments' => array('%foo%')
            ));

        $this->assertTrue($c->get('__this__') === $c);
        $this->assertTrue($c->get('cnt') instanceof DependencyInjectionContainer);
        $this->assertTrue($c->get('foo') instanceof Foo);
        $this->assertTrue($c->get('foo')->param instanceof Bar);
        $this->assertTrue($c->get('foo') === $c->get('foo'));
        $this->assertTrue($c->get('bar') !== new Bar('whatever'));
        $this->assertTrue($c->get('bar') !== $c->get('bar2'));
        $this->assertTrue($c->get('bar2')->param === '001');
        $this->assertTrue($c->get('bar3') instanceof Bar);
        $this->assertTrue($c->get('bar3')->param === $c->get('foo'));
    }
}

class Foo
{
    public $param;

    public function __construct($param)
    {
        $this->param = $param;
    }
}

class Bar
{
    public $param;

    public function __construct($param)
    {
        $this->param = $param;
    }
}