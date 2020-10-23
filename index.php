<?php

include 'PhpHBObfuscator.php';

$code = file_get_contents("hb_test.php");

$hb = new PhpHBObfuscator();
$newcode = $hb->do_hb($code);
file_put_contents("result.php", $newcode);

