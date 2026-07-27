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

$login = WireEncoder::string(2,'BD').WireEncoder::string(8,'jwt-token').WireEncoder::uint(9,3600).WireEncoder::string(10,'example.freefire.invalid');
$http = new QueueTransport(
    new HttpResponse(200,[],json_encode(['access_token'=>'guest-token','open_id'=>'open-id'],JSON_THROW_ON_ERROR)),
    new HttpResponse(200,[],$login),
    new HttpResponse(200,[],'player-binary'),
);
$dir=sys_get_temp_dir().'/freefire-mock-'.bin2hex(random_bytes(4));$cache=new FileCacheStore($dir);$profile=new Ob54ProtocolProfile();
$tokens=new TokenManager($profile,new BundledCredentialProvider(),$http,$cache);
$first=$tokens->get('BD');$second=$tokens->get('BD');
if($first->bearerToken!=='Bearer jwt-token'||$second->serverUrl!=='example.freefire.invalid'||count($http->requests)!==2)throw new RuntimeException('Token workflow/cache failed.');
$client=new FreeFireClient($profile,$tokens,$http,new FakePlayerDecoder(),$cache);
$player=$client->player('4422076728','BD');$cached=$client->player('4422076728','BD');
if(($player['basicInfo']['nickname']??'')!=='Fixture Player'||$cached!==$player||count($http->requests)!==3)throw new RuntimeException('Player workflow/cache failed.');
if(($http->requests[2]->headers['Authorization']??'')!=='Bearer jwt-token')throw new RuntimeException('Authorization header missing.');
array_map('unlink',glob($dir.'/*')?:[]);@rmdir($dir);
echo "Mock token/player integration passed.\n";
