<?php
declare(strict_types=1);

namespace Refatbd\FreeFire\Player;

use Google\Protobuf\Internal\Message;
use Refatbd\FreeFire\Exception\ConfigurationException;
use Refatbd\FreeFire\Exception\ProtocolException;

final class GoogleProtobufPlayerResponseDecoder implements PlayerResponseDecoderInterface
{
    /** @param class-string $messageClass */
    public function __construct(
        private readonly string $messageClass = 'Refatbd\\FreeFire\\Protocol\\Generated\\Ob54\\AccountPersonalShow\\AccountPersonalShowInfo',
        private readonly PlayerDataNormalizer $normalizer = new PlayerDataNormalizer(),
    ) {}

    public function decode(string $bytes): array
    {
        if (class_exists($this->messageClass)) {
            try {
                $message = new ($this->messageClass)();
                if ($message instanceof Message) {
                    $message->mergeFromString($bytes);
                    $data = json_decode(
                        $message->serializeToJsonString(),
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    );
                    if (is_array($data)) {
                        return $this->normalizer->normalize($data);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to WireDecoder
            }
        }

        try {
            $data = $this->decodeWire($bytes);
            return $this->normalizer->normalize($data);
        } catch (\Throwable $e) {
            throw new ProtocolException('Could not decode player response with wire decoder: '.$e->getMessage(), 0, $e);
        }
    }

    /** @return array<string,mixed> */
    private function decodeWire(string $bytes): array
    {
        $fields = \Refatbd\FreeFire\Protocol\Wire\WireDecoder::fields($bytes);
        $data = [];

        $basicFields = [
            1 => ['accountId', 0, 'int'],
            2 => ['accountType', 0, 'int'],
            3 => ['nickname', 2, 'string'],
            4 => ['externalId', 2, 'string'],
            5 => ['region', 2, 'string'],
            6 => ['level', 0, 'int'],
            7 => ['exp', 0, 'int'],
            11 => ['bannerId', 0, 'int'],
            12 => ['headPic', 0, 'int'],
            13 => ['clanName', 2, 'string'],
            14 => ['rank', 0, 'int'],
            15 => ['rankingPoints', 0, 'int'],
            18 => ['badgeCnt', 0, 'int'],
            19 => ['badgeId', 0, 'int'],
            20 => ['seasonId', 0, 'int'],
            21 => ['liked', 0, 'int'],
            24 => ['lastLoginAt', 0, 'int'],
            30 => ['csRank', 0, 'int'],
            31 => ['csRankingPoints', 0, 'int'],
            33 => ['pinId', 0, 'int'],
            35 => ['maxRank', 0, 'int'],
            36 => ['csMaxRank', 0, 'int'],
            44 => ['createAt', 0, 'int'],
            48 => ['title', 0, 'int'],
            50 => ['releaseVersion', 2, 'string'],
            52 => ['showBrRank', 0, 'bool'],
            53 => ['showCsRank', 0, 'bool']
        ];

        $socialFields = [
            1 => ['accountId', 0, 'int'],
            2 => ['gender', 0, 'int'],
            3 => ['language', 0, 'int'],
            8 => ['modePrefer', 0, 'int'],
            9 => ['signature', 2, 'string'],
            10 => ['rankShow', 0, 'int']
        ];

        $profileFields = [
            1 => ['avatarId', 0, 'int'],
            4 => ['clothes', 2, 'array'],
            5 => ['equipedSkills', 2, 'array'],
            8 => ['isSelectedAwaken', 0, 'bool']
        ];

        $petFields = [
            1 => ['id', 0, 'int'],
            2 => ['name', 2, 'string'],
            3 => ['level', 0, 'int'],
            4 => ['exp', 0, 'int'],
            5 => ['isSelected', 0, 'bool'],
            6 => ['skinId', 0, 'int'],
            9 => ['selectedSkillId', 0, 'int']
        ];

        $clanFields = [
            1 => ['clanId', 0, 'int'],
            2 => ['clanName', 2, 'string'],
            3 => ['captainId', 0, 'int'],
            4 => ['clanLevel', 0, 'int'],
            5 => ['capacity', 0, 'int'],
            6 => ['memberNum', 0, 'int']
        ];

        $captainFields = [
            1 => ['accountId', 0, 'int'],
            2 => ['accountType', 0, 'int'],
            3 => ['nickname', 2, 'string'],
            5 => ['region', 2, 'string'],
            6 => ['level', 0, 'int'],
            7 => ['exp', 0, 'int'],
            21 => ['liked', 0, 'int'],
            24 => ['lastLoginAt', 0, 'int'],
            44 => ['createAt', 0, 'int']
        ];

        $diamondFields = [1 => ['diamondCost', 0, 'int']];
        $creditFields = [1 => ['creditScore', 0, 'int']];

        $decodeMessage = function(string $msgBytes, array $map) {
            $res = [];
            try {
                $subFields = \Refatbd\FreeFire\Protocol\Wire\WireDecoder::fields($msgBytes);
                foreach ($subFields as $sf) {
                    $fNum = $sf['field'];
                    $wire = $sf['wire'];
                    $val = $sf['value'];
                    if (isset($map[$fNum])) {
                        [$key, $expWire, $type] = $map[$fNum];
                        if ($wire === $expWire) {
                            if ($type === 'array') {
                                $res[$key][] = (int)$val;
                            } elseif ($type === 'int') {
                                $res[$key] = (int)$val;
                            } elseif ($type === 'bool') {
                                $res[$key] = (bool)$val;
                            } else {
                                $res[$key] = (string)$val;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {}
            return $res;
        };

        foreach ($fields as $f) {
            $num = $f['field'];
            $val = $f['value'];
            if (!is_string($val)) continue;

            if ($num === 1) {
                $data['basicInfo'] = $decodeMessage($val, $basicFields);
            } elseif ($num === 2) {
                $data['profileInfo'] = $decodeMessage($val, $profileFields);
            } elseif ($num === 6) {
                $data['clanBasicInfo'] = $decodeMessage($val, $clanFields);
            } elseif ($num === 7) {
                $data['captainBasicInfo'] = $decodeMessage($val, $captainFields);
            } elseif ($num === 8) {
                $data['petInfo'] = $decodeMessage($val, $petFields);
            } elseif ($num === 9) {
                $data['socialInfo'] = $decodeMessage($val, $socialFields);
            } elseif ($num === 10) {
                $data['diamondCostRes'] = $decodeMessage($val, $diamondFields);
            } elseif ($num === 11) {
                $data['creditScoreInfo'] = $decodeMessage($val, $creditFields);
            }
        }

        return $data;
    }
}
