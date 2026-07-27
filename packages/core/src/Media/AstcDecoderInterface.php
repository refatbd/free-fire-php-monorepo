<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Media;
interface AstcDecoderInterface { public function available(): bool; public function decodeFile(string $inputAstc,string $outputPng): void; public function name(): string; }
