<?php
declare(strict_types=1);
$vendor=dirname(__DIR__).'/vendor/autoload.php';
if(!is_file($vendor)){
    if(getenv('REQUIRE_GENERATED_PROTO')==='1'){fwrite(STDERR,"Vendor autoload is required.\n");exit(1);}echo "Generated Protobuf test skipped (Composer vendor unavailable).\n";exit(0);
}
require $vendor;
use Refatbd\FreeFire\Protocol\LoginRequestCodec;
use Refatbd\FreeFire\Protocol\PlayerRequestCodec;
$loginClass='Refatbd\\FreeFire\\Protocol\\Generated\\Ob54\\LegacyLogin\\LoginReq';
$playerClass='Refatbd\\FreeFire\\Protocol\\Generated\\Ob54\\PlayerRequest\\GetPlayerPersonalShow';
$responseClass='Refatbd\\FreeFire\\Protocol\\Generated\\Ob54\\AccountPersonalShow\\AccountPersonalShowInfo';
foreach([$loginClass,$playerClass,$responseClass] as $class){if(!class_exists($class)){fwrite(STDERR,"Missing generated class {$class}.\n");exit(1);}}
$login=new $loginClass();$login->setOpenId('open')->setOpenIdType('4')->setLoginToken('token')->setOrignPlatformType('4');
if(!hash_equals((new LoginRequestCodec())->encode('open','token'),$login->serializeToString())){fwrite(STDERR,"Login request byte parity failed.\n");exit(1);}
$player=new $playerClass();$player->setA(4422076728)->setB(7);
if(!hash_equals((new PlayerRequestCodec())->encode('4422076728'),$player->serializeToString())){fwrite(STDERR,"Player request byte parity failed.\n");exit(1);}
echo "Generated Protobuf request parity passed.\n";
