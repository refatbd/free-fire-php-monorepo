<?php
declare(strict_types=1);
$root=dirname(__DIR__);$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));$failed=[];
foreach($iterator as $file){if($file->getExtension()!=='php'||str_contains($file->getPathname(),DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR))continue;$cmd=[PHP_BINARY,'-l',$file->getPathname()];$p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);if($code!==0)$failed[]=$file->getPathname()."\n".$out;}
if($failed){fwrite(STDERR,implode("\n",$failed));exit(1);}echo "PHP syntax validation passed.\n";
