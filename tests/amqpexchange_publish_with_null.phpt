--TEST--
AMQPExchange::publish() body with null-byte
--SKIPIF--
<?php if (!extension_loaded("amqp")) print "skip"; ?>
--FILE--
<?php

class Foo
{
    private $bar = 'bar';
    protected $baz = 'baz';

    public function __construct($bar, $baz) {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}

$c = new AMQPConnection(array('read_timeout' => 5));
$c->connect();
$channel = new AMQPChannel($c);

$q_name = 'test_' . microtime(true);

$q = new AMQPQueue($channel);
$q->setName($q_name);
$q->setFlags(AMQP_AUTODELETE);
$q->declareQueue();

$ex = new AMQPExchange($channel);
$orig1= new Foo('x1', 'y1');
$orig2= new Foo('x2', 'y2');
$s1 = serialize($orig1);
$s2 = serialize($orig2);


echo 'Orig 1:', PHP_EOL;
debug_zval_dump($orig1);
debug_zval_dump($s1);

echo PHP_EOL;

echo 'Orig 2:', PHP_EOL;
debug_zval_dump($orig2);
debug_zval_dump($s2);

echo PHP_EOL;


$ex->publish($s1, $q_name);
$ex->publish($s2, $q_name);


echo 'basic.get:', PHP_EOL;
$msg = $q->get();
debug_zval_dump($msg->getBody());
$restored = unserialize($msg->getBody());
debug_zval_dump($restored);

echo PHP_EOL;

$q->consume(function ($msg) {
    echo 'basic.consume:', PHP_EOL;

    debug_zval_dump($msg->getBody());
    $restored = unserialize($msg->getBody());
    debug_zval_dump($restored);

    return false;
});


?>
--EXPECT--
Orig 1:
object(Foo)#5 (2) refcount(2){
  ["bar":"Foo":private]=>
  string(2) "x1" refcount(1)
  ["baz":protected]=>
  string(2) "y1" refcount(1)
}
string(60) "O:3:"Foo":2:{s:8:" Foo bar";s:2:"x1";s:6:" * baz";s:2:"y1";}" refcount(2)

Orig 2:
object(Foo)#6 (2) refcount(2){
  ["bar":"Foo":private]=>
  string(2) "x2" refcount(1)
  ["baz":protected]=>
  string(2) "y2" refcount(1)
}
string(60) "O:3:"Foo":2:{s:8:" Foo bar";s:2:"x2";s:6:" * baz";s:2:"y2";}" refcount(2)

basic.get:
string(60) "O:3:"Foo":2:{s:8:" Foo bar";s:2:"x1";s:6:" * baz";s:2:"y1";}" refcount(1)
object(Foo)#8 (2) refcount(2){
  ["bar":"Foo":private]=>
  string(2) "x1" refcount(1)
  ["baz":protected]=>
  string(2) "y1" refcount(1)
}

basic.consume:
string(60) "O:3:"Foo":2:{s:8:" Foo bar";s:2:"x2";s:6:" * baz";s:2:"y2";}" refcount(1)
object(Foo)#11 (2) refcount(2){
  ["bar":"Foo":private]=>
  string(2) "x2" refcount(1)
  ["baz":protected]=>
  string(2) "y2" refcount(1)
}