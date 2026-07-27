<?php
declare(strict_types=1);
spl_autoload_register(function(string $class):void{$prefix='Refatbd\\FreeFire\\';if(!str_starts_with($class,$prefix))return;$file=dirname(__DIR__).'/packages/core/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($file))require $file;});
