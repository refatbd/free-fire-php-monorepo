<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Media;
use Refatbd\FreeFire\Exception\MediaException;
final class NullAstcDecoder implements AstcDecoderInterface { public function available(): bool{return false;} public function name(): string{return 'none';} public function decodeFile(string $inputAstc,string $outputPng): void{throw new MediaException('No ASTC decoder is configured.');} }
