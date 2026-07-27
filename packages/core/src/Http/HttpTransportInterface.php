<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Http;
interface HttpTransportInterface { public function send(HttpRequest $request): HttpResponse; }
