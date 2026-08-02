<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
use Refatbd\FreeFire\Credentials\BundledCredentialProvider;
use Refatbd\FreeFire\Credentials\CredentialGroupResolver;
use Refatbd\FreeFire\Credentials\EnvironmentCredentialProvider;
use Refatbd\FreeFire\Crypto\AesCbcCipher;
use Refatbd\FreeFire\Media\AstcHeaderParser;
use Refatbd\FreeFire\Http\StreamHttpTransport;
use Refatbd\FreeFire\Media\OfficialAssetPolicy;
use Refatbd\FreeFire\Media\MediaService;
use Refatbd\FreeFire\Media\MediaVersion;
use Refatbd\FreeFire\Media\PlayerMediaRendererInterface;
use Refatbd\FreeFire\Media\RenderedMedia;
use Refatbd\FreeFire\Cache\FileCacheStore;
use Refatbd\FreeFire\Protocol\BuiltInProtocolProfiles;
use Refatbd\FreeFire\Protocol\LoginRequestCodec;
use Refatbd\FreeFire\Protocol\PlayerRequestCodec;
use Refatbd\FreeFire\Protocol\Profiles\Ob54ProtocolProfile;
use Refatbd\FreeFire\Protocol\ProtocolProfileRegistry;
use Refatbd\FreeFire\Protocol\Wire\WireDecoder;
use Refatbd\FreeFire\Protocol\Wire\WireEncoder;
use Refatbd\FreeFire\Region\RegionRegistry;
use Refatbd\FreeFire\Region\ServerUrlPolicy;
use Refatbd\FreeFire\Validation\InputValidator;
$tests=[];$assert=function(bool $ok,string $name)use(&$tests){if(!$ok)throw new RuntimeException("Failed: {$name}");$tests[]=$name;};
$p=new Ob54ProtocolProfile();$assert(strlen($p->encryptionKey())===16&&strlen($p->encryptionIv())===16,'OB54 key/IV length');
$profiles=new ProtocolProfileRegistry([$p]);$assert($profiles->get('ob54')===$p&&$profiles->versions()===['OB54'],'protocol profile registry');
$assert(BuiltInProtocolProfiles::classes()['OB54']===Ob54ProtocolProfile::class&&BuiltInProtocolProfiles::get('OB54')->obVersion()==='OB54','built-in protocol profile registry');
$assert($p->playerShowPath()==='/GetPlayerPersonalShow'&&$p->playerCallSignSource()===7,'profile-driven player request settings');
$assert(str_contains($p->playerResponseMessageClass(),'Generated\\Ob54\\'),'OB-versioned generated response class');
$r=new RegionRegistry();$assert($r->normalize('eu')==='EUROPE','region alias');$assert(InputValidator::uid('4422076728')==='4422076728','UID validation');
$uint64=WireEncoder::varint('18446744073709551615');$assert(bin2hex($uint64)==='ffffffffffffffffff01','uint64 varint encoding');
$uint64Field=WireDecoder::fields(WireEncoder::key(1,0).$uint64);$assert(($uint64Field[0]['value']??null)==='18446744073709551615','uint64 varint decoding');
try{InputValidator::uid('9223372036854775808');$assert(false,'UID int64 range rejection');}catch(\Throwable){$assert(true,'UID int64 range rejection');}
$serverPolicy=new ServerUrlPolicy();$assert($serverPolicy->normalize('example.freefire.invalid/')==='https://example.freefire.invalid','server URL normalization');
try{$serverPolicy->normalize('http://127.0.0.1:8080');$assert(false,'private server URL rejection');}catch(\Throwable){$assert(true,'private server URL rejection');}
$c=(new BundledCredentialProvider())->forRegion('BD');$assert($c!==null&&$c->uid==='3692265171','bundled global credential');
$assert(CredentialGroupResolver::forRegion('BR')==='AMERICAS'&&CredentialGroupResolver::forRegion('BD')==='GLOBAL','credential group mapping');
$environmentPrefix='FREEFIRE_SMOKE_'.strtoupper(bin2hex(random_bytes(4)));
putenv("{$environmentPrefix}_AMERICAS_UID=123456789");
putenv("{$environmentPrefix}_AMERICAS_PASSWORD=group-password");
$environmentCredential=(new EnvironmentCredentialProvider($environmentPrefix))->forRegion('BR');
$assert($environmentCredential?->uid==='123456789'&&$environmentCredential?->password==='group-password','environment credential group fallback');
putenv("{$environmentPrefix}_BR_UID=987654321");
$environmentCredential=(new EnvironmentCredentialProvider($environmentPrefix))->forRegion('BR');
$assert($environmentCredential?->uid==='123456789','incomplete region pair does not mix with group pair');
putenv("{$environmentPrefix}_BR_PASSWORD=region-password");
$environmentCredential=(new EnvironmentCredentialProvider($environmentPrefix))->forRegion('BR');
$assert($environmentCredential?->uid==='987654321'&&$environmentCredential?->password==='region-password','complete region pair overrides group pair');
foreach(['BR_UID','BR_PASSWORD','AMERICAS_UID','AMERICAS_PASSWORD'] as $suffix)putenv("{$environmentPrefix}_{$suffix}");
$login=(new LoginRequestCodec())->encode('open','token');$fields=WireDecoder::fields($login);$assert(array_column($fields,'field')===[22,23,29,99],'login protobuf field numbers');
$player=(new PlayerRequestCodec())->encode('4422076728');$assert(array_column(WireDecoder::fields($player),'field')===[1,2],'player protobuf field numbers');
$cipher=(new AesCbcCipher())->encrypt($player,$p->encryptionKey(),$p->encryptionIv());$assert(strlen($cipher)%16===0,'AES block alignment');
$golden=json_decode((string)file_get_contents(__DIR__.'/Fixtures/Protocol/OB54/request-golden.json'),true,512,JSON_THROW_ON_ERROR);
$goldenLogin=(new LoginRequestCodec())->encode($golden['input']['openId'],$golden['input']['loginToken']);
$goldenPlayer=(new PlayerRequestCodec())->encode($golden['input']['uid'],(int)$golden['input']['callSignSource']);
$goldenCipher=(new AesCbcCipher())->encrypt($goldenPlayer,$p->encryptionKey(),$p->encryptionIv());
$assert(bin2hex($goldenLogin)===$golden['expected']['loginRequestHex'],'Python/PHP login request golden parity');
$assert(bin2hex($goldenPlayer)===$golden['expected']['playerRequestHex'],'Python/PHP player request golden parity');
$assert(bin2hex($goldenCipher)===$golden['expected']['playerEncryptedHex'],'Python/PHP AES golden parity');
$header=AstcHeaderParser::MAGIC.chr(6).chr(6).chr(1)."\x00\x02\x00"."\x00\x01\x00"."\x01\x00\x00";$parsed=(new AstcHeaderParser())->parse($header);$assert($parsed->width===512&&$parsed->height===256,'ASTC header parsing');
$smallHeader=AstcHeaderParser::MAGIC.chr(6).chr(6).chr(1)."\x06\x00\x00"."\x06\x00\x00"."\x01\x00\x00";
try{(new AstcHeaderParser())->validateAsset($smallHeader);$assert(false,'truncated ASTC rejection');}catch(\Throwable){$assert(true,'truncated ASTC rejection');}
$assert((new AstcHeaderParser())->validateAsset($smallHeader.str_repeat("\0",16))->width===6,'complete ASTC payload validation');
$transport=new StreamHttpTransport();$method=new ReflectionMethod($transport,'parseHeaders');$method->setAccessible(true);[$status,$parsedHeaders]=$method->invoke($transport,['HTTP/1.1 100 Continue','X-Interim: yes','HTTP/1.1 200 OK','Content-Type: application/octet-stream']);$assert($status===200&&!isset($parsedHeaders['x-interim'])&&($parsedHeaders['content-type'][0]??'')==='application/octet-stream','final HTTP response block parsing');

$policy=new OfficialAssetPolicy([OfficialAssetPolicy::KNOWN_BASES[1], 'https://evil.example/assets']);
$urls=$policy->urls('901000116');
$assert(count($urls)===1&&str_starts_with($urls[0],OfficialAssetPolicy::KNOWN_BASES[1]),'official asset base allowlist');
$assert($policy->isAllowedUrl($urls[0]),'official asset URL accepted');
$assert(!$policy->isAllowedUrl('https://dl.tata.freefiremobile.com.evil.example/live/ABHotUpdates/IconCDN/android/901000116_rgb.astc'),'lookalike CDN rejected');
$assert(!$policy->isAllowedUrl('https://dl.tata.freefiremobile.com:444/live/ABHotUpdates/IconCDN/android/901000116_rgb.astc'),'nonstandard CDN port rejected');

$cacheDir=sys_get_temp_dir().'/freefire-cache-smoke-'.bin2hex(random_bytes(4));
$cache=new FileCacheStore($cacheDir);
$cache->put('replace-me',['value'=>1],60);
$cache->put('replace-me',['value'=>2],60);
$assert($cache->get('replace-me')===['value'=>2],'atomic file cache replacement');
$unsafeFile=$cacheDir.'/'.hash('sha256','unsafe-object').'.cache';
file_put_contents($unsafeFile,serialize(['expires'=>time()+60,'value'=>new stdClass()]));
$assert($cache->get('unsafe-object')===null&&!is_file($unsafeFile),'unsafe cached object rejection');
$lockOwner=$cache->acquireLock('fixture',30);
$assert(is_string($lockOwner)&&$cache->acquireLock('fixture',30)===null,'file cache exclusive lock');
$cache->releaseLock('fixture',(string)$lockOwner);
$assert(is_string($cache->acquireLock('fixture',30)),'file cache lock release');
array_map('unlink',glob($cacheDir.'/*')?:[]);@rmdir($cacheDir);

$mediaDir=sys_get_temp_dir().'/freefire-media-smoke-'.bin2hex(random_bytes(4));
$renderer=new class implements PlayerMediaRendererInterface {
    public int $calls=0;
    public function available():bool{return true;}
    public function avatar(array $player,int $size=512):RenderedMedia{$this->calls++;return new RenderedMedia('avatar-'.$size);}
    public function banner(array $player,int $width=1000,int $height=250,bool $raw=false):RenderedMedia{$this->calls++;return new RenderedMedia("banner-{$width}x{$height}");}
};
$media=new MediaService($renderer,new FileCacheStore($mediaDir),'OB54',60);
$fixture=['basicInfo'=>['accountId'=>'4422076728','headPic'=>902050009,'bannerId'=>901000116,'nickname'=>'Fixture','level'=>67],'clanBasicInfo'=>['clanName'=>'Guild']];
$fixture['mediaInfo']['obVersion']='OB54';$avatarVersion=MediaVersion::avatar($fixture);$bannerVersion=MediaVersion::banner($fixture);$fixtureChanged=$fixture;$fixtureChanged['basicInfo']['level']=68;
$assert($avatarVersion===MediaVersion::avatar($fixtureChanged)&&$bannerVersion!==MediaVersion::banner($fixtureChanged),'deterministic media version fields');
$fallbackA=['basicInfo'=>['accountId'=>'10001'],'mediaInfo'=>['obVersion'=>'OB54']];$fallbackB=['basicInfo'=>['accountId'=>'10002'],'mediaInfo'=>['obVersion'=>'OB54']];
$assert(MediaVersion::avatar($fallbackA)!==MediaVersion::avatar($fallbackB)&&MediaVersion::banner($fallbackA)!==MediaVersion::banner($fallbackB),'fallback media version includes account identity');
$assert($media->avatar($fixture,256)->data==='avatar-256'&&$media->avatar($fixture,256)->data==='avatar-256'&&$renderer->calls===1,'rendered media cache');
$assert($media->avatar($fixture,9999)->data==='avatar-1024'&&$media->avatar($fixture,1024)->data==='avatar-1024'&&$renderer->calls===2,'normalized avatar cache dimensions');
$firstBanner=$media->banner($fixture,1000,250);$fixture['basicInfo']['level']=68;$secondBanner=$media->banner($fixture,1000,250);
$assert($firstBanner->data==='banner-1000x250'&&$secondBanner->data==='banner-1000x250'&&$renderer->calls===4,'banner cache includes rendered player fields');
array_map('unlink',glob($mediaDir.'/*')?:[]);@rmdir($mediaDir);

echo 'Passed '.count($tests)." smoke tests:\n - ".implode("\n - ",$tests)."\n";
