<?php
declare(strict_types=1);
require __DIR__.'/psr-log-stub.php';
require __DIR__.'/bootstrap.php';

use Refatbd\FreeFire\Cache\FileCacheStore;
use Refatbd\FreeFire\Credentials\BundledCredentialProvider;
use Refatbd\FreeFire\FreeFireClient;
use Refatbd\FreeFire\Http\HttpRequest;
use Refatbd\FreeFire\Http\HttpResponse;
use Refatbd\FreeFire\Http\HttpTransportInterface;
use Refatbd\FreeFire\Player\PlayerResponseDecoderInterface;
use Refatbd\FreeFire\Protocol\Profiles\Ob54ProtocolProfile;
use Refatbd\FreeFire\Protocol\Profiles\Ob55ProtocolProfile;
use Refatbd\FreeFire\Protocol\Wire\WireEncoder;
use Refatbd\FreeFire\Token\TokenManager;

final class QueueTransport implements HttpTransportInterface
{
    /** @var list<HttpResponse> */ public array $responses;
    /** @var list<HttpRequest> */ public array $requests=[];
    public function __construct(HttpResponse ...$responses){$this->responses=$responses;}
    public function send(HttpRequest $request):HttpResponse{$this->requests[]=$request;return array_shift($this->responses)??throw new RuntimeException('Unexpected HTTP request.');}
}
final class FakePlayerDecoder implements PlayerResponseDecoderInterface
{
    public function decode(string $bytes):array{if($bytes!=='player-binary')throw new RuntimeException('Wrong player payload.');return ['basicInfo'=>['accountId'=>'4422076728','nickname'=>'Fixture Player']];}
}

$login = WireEncoder::string(2,'BD').WireEncoder::string(8,'jwt-token').WireEncoder::uint(9,3600).WireEncoder::string(10,'https://clientbp.ppmainecoonghj.com');
$http = new QueueTransport(
    new HttpResponse(200,[],json_encode(['access_token'=>'guest-token','open_id'=>'open-id'],JSON_THROW_ON_ERROR)),
    new HttpResponse(200,[],$login),
    new HttpResponse(200,[],'player-binary'),
);
$dir=sys_get_temp_dir().'/freefire-mock-'.bin2hex(random_bytes(4));$cache=new FileCacheStore($dir);$profile=new Ob54ProtocolProfile();
$tokens=new TokenManager($profile,new BundledCredentialProvider(),$http,$cache);
$first=$tokens->get('BD');$second=$tokens->get('BD');
if($first->bearerToken!=='Bearer jwt-token'||$second->serverUrl!=='https://clientbp.ppmainecoonghj.com'||count($http->requests)!==2)throw new RuntimeException('Token workflow/cache failed.');
$client=new FreeFireClient($profile,$tokens,$http,new FakePlayerDecoder(),$cache);
$player=$client->player('4422076728','BD');$cached=$client->player('4422076728','BD');
if(($player['basicInfo']['nickname']??'')!=='Fixture Player'||$cached!==$player||count($http->requests)!==3)throw new RuntimeException('Player workflow/cache failed.');
if(($http->requests[2]->headers['Authorization']??'')!=='Bearer jwt-token')throw new RuntimeException('Authorization header missing.');
$ob55=new Ob55ProtocolProfile();
$ob55Http=new QueueTransport(
    new HttpResponse(200,[],json_encode(['access_token'=>'guest-token','open_id'=>'open-id'],JSON_THROW_ON_ERROR)),
    new HttpResponse(200,[],str_repeat("\0",64).$login),
    new HttpResponse(200,[],'player-binary'),
);
$ob55Tokens=new TokenManager($ob55,new BundledCredentialProvider(),$ob55Http,$cache);
$ob55Client=new FreeFireClient($ob55,$ob55Tokens,$ob55Http,new FakePlayerDecoder(),$cache);
if(($ob55Client->player('4422076728','BD')['basicInfo']['nickname']??'')!=='Fixture Player')throw new RuntimeException('OB55 player workflow failed.');
if(($ob55Http->requests[1]->headers['ReleaseVersion']??'')!=='OB55'||!ctype_digit($ob55Http->requests[1]->headers['X-GA-SV']??''))throw new RuntimeException('OB55 login headers missing.');
if(($ob55Http->requests[2]->headers['ReleaseVersion']??'')!=='OB55'||!ctype_digit($ob55Http->requests[2]->headers['X-GA-SV']??''))throw new RuntimeException('OB55 player headers missing.');
$unknownStatus=WireEncoder::string(13,WireEncoder::uint(1,1).WireEncoder::uint(3,1756478597));
$failedHttp=new QueueTransport(
    new HttpResponse(200,[],json_encode(['access_token'=>'guest-token','open_id'=>'open-id'],JSON_THROW_ON_ERROR)),
    new HttpResponse(200,[],str_repeat("\0",64).$unknownStatus),
);
try{(new TokenManager($ob55,new BundledCredentialProvider(),$failedHttp,$cache))->get('BR');throw new RuntimeException('Tokenless OB55 reply was accepted.');}
catch(\Refatbd\FreeFire\Exception\ProtocolException){}
if($cache->get('freefire:OB55:token:BR')!==null)throw new RuntimeException('Tokenless OB55 reply was cached.');
$offlineHttp=new QueueTransport();
$offlineTokens=new TokenManager($ob55,new BundledCredentialProvider(),$offlineHttp,$cache);
$offlineClient=new FreeFireClient($ob55,$offlineTokens,$offlineHttp,new FakePlayerDecoder(),$cache);
try{$offlineClient->player('123456789','BR');throw new RuntimeException('Explicit gateway failure was swallowed.');}
catch(RuntimeException $error){if($error->getMessage()==='Explicit gateway failure was swallowed.')throw $error;}
try{$offlineClient->player('123456789');throw new RuntimeException('Partial gateway failure was treated as not found.');}
catch(\Refatbd\FreeFire\Exception\LookupIncompleteException $error){if(!str_contains($error->getMessage(),'could not be completed'))throw $error;}
array_map('unlink',glob($dir.'/*')?:[]);@rmdir($dir);
echo "Mock token/player integration passed.\n";
