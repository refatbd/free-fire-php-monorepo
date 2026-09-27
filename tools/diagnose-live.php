<?php
declare(strict_types=1);

// Manual live check only. CI never calls this script or prints credentials.
$root = dirname(__DIR__);
if (is_file($root.'/vendor/autoload.php')) {
    require $root.'/vendor/autoload.php';
} else {
    require $root.'/tests/psr-log-stub.php';
    require $root.'/tests/bootstrap.php';
}

use Refatbd\FreeFire\Cache\CacheStoreInterface;
use Refatbd\FreeFire\Credentials\BundledCredentialProvider;
use Refatbd\FreeFire\Credentials\ChainCredentialProvider;
use Refatbd\FreeFire\Credentials\EnvironmentCredentialProvider;
use Refatbd\FreeFire\FreeFireClient;
use Refatbd\FreeFire\Http\StreamHttpTransport;
use Refatbd\FreeFire\Player\GoogleProtobufPlayerResponseDecoder;
use Refatbd\FreeFire\Protocol\BuiltInProtocolProfiles;
use Refatbd\FreeFire\Token\TokenManager;

$regions = ['IND', 'BR', 'VN', 'ID', 'TH', 'TW', 'BD'];
$selfLookup = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--self-lookup') {
        $selfLookup = true;
    } elseif (str_starts_with($argument, '--regions=')) {
        $regions = array_values(array_filter(array_map('trim', explode(',', substr($argument, 10)))));
    } else {
        fwrite(STDERR, "Usage: php tools/diagnose-live.php [--regions=IND,BR,VN,ID,TH,TW,BD] [--self-lookup]\n");
        exit(2);
    }
}

$cache = new class implements CacheStoreInterface {
    private array $values = [];
    public function get(string $key): mixed { return $this->values[$key] ?? null; }
    public function put(string $key, mixed $value, int $ttlSeconds): void { $this->values[$key] = $value; }
    public function forget(string $key): void { unset($this->values[$key]); }
    public function acquireLock(string $key, int $ttlSeconds): ?string { return 'diagnostic'; }
    public function releaseLock(string $key, string $owner): void {}
};

$profile = BuiltInProtocolProfiles::get('OB55');
$http = new StreamHttpTransport();
$credentials = new ChainCredentialProvider([new EnvironmentCredentialProvider(), new BundledCredentialProvider()]);
$tokens = new TokenManager($profile, $credentials, $http, $cache);
$client = new FreeFireClient($profile, $tokens, $http,
    new GoogleProtobufPlayerResponseDecoder($profile->playerResponseMessageClass()), $cache);
$failed = false;
foreach ($regions as $region) {
    $result = ['region' => strtoupper($region), 'protocol' => $profile->obVersion()];
    try {
        $token = $tokens->get($region, true);
        $result['login'] = 'ok';
        $result['lock_region'] = $token->region;
        $result['server_host'] = parse_url($token->serverUrl, PHP_URL_HOST);
        if ($selfLookup) {
            $jwtParts = explode('.', substr($token->bearerToken, 7));
            $claims = json_decode(base64_decode(strtr($jwtParts[1] ?? '', '-_', '+/'), true) ?: '', true, 512, JSON_THROW_ON_ERROR);
            $uid = (string) ($claims['account_id'] ?? '');
            if (!ctype_digit($uid)) {
                throw new RuntimeException('Login token has no account ID.');
            }
            $player = $client->player($uid, $region);
            $result['player_found'] = !empty($player['basicInfo']['nickname']);
            $result['sections'] = array_keys($player);
            sort($result['sections']);
            $failed = $failed || !$result['player_found'];
        }
    } catch (Throwable $error) {
        $failed = true;
        $result['error_type'] = $error::class;
    }
    echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
}
exit($failed ? 1 : 0);
