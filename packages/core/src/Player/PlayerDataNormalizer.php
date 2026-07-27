<?php
declare(strict_types=1);
namespace Refatbd\FreeFire\Player;

final class PlayerDataNormalizer
{
    /** @var array<string,string> */
    private const KEYS = [
        'basicinfo'=>'basicInfo','clanbasicinfo'=>'clanBasicInfo','captainbasicinfo'=>'captainBasicInfo','socialinfo'=>'socialInfo',
        'profileinfo'=>'profileInfo','rankingleaderboardpos'=>'rankingLeaderboardPos','historyepinfo'=>'historyEpInfo','petinfo'=>'petInfo',
        'diamondcostres'=>'diamondCostRes','creditscoreinfo'=>'creditScoreInfo','creditscoreinfobasic'=>'creditScoreInfoBasic',
        'preveterantype'=>'preVeteranType','mmrlist'=>'mmrList','modestatssummaryinfo'=>'modeStatsSummaryInfo',
        'accountid'=>'accountId','accounttype'=>'accountType','externalid'=>'externalId','externaltype'=>'externalType','externalname'=>'externalName',
        'externalicon'=>'externalIcon','bannerid'=>'bannerId','headpic'=>'headPic','clanname'=>'clanName','rankingpoints'=>'rankingPoints',
        'haselitepass'=>'hasElitePass','badgecnt'=>'badgeCnt','badgeid'=>'badgeId','seasonid'=>'seasonId','isdeleted'=>'isDeleted',
        'showrank'=>'showRank','lastloginat'=>'lastLoginAt','externaluid'=>'externalUid','returnat'=>'returnAt','championshipteamname'=>'championshipTeamName',
        'championshipteammembernum'=>'championshipTeamMemberNum','championshipteamid'=>'championshipTeamId','csrank'=>'csRank','csrankingpoints'=>'csRankingPoints',
        'weaponskinshows'=>'weaponSkinShows','pinid'=>'pinId','iscsrankingban'=>'isCsRankingBan','maxrank'=>'maxRank','csmaxrank'=>'csMaxRank',
        'maxrankingpoints'=>'maxRankingPoints','gamebagshow'=>'gameBagShow','peakrankpos'=>'peakRankPos','cspeakrankpos'=>'csPeakRankPos',
        'accountprefers'=>'accountPrefers','periodicrankingpoints'=>'periodicRankingPoints','periodicrank'=>'periodicRank','createat'=>'createAt',
        'releaseversion'=>'releaseVersion','showbrrank'=>'showBrRank','showcsrank'=>'showCsRank','clanid'=>'clanId','clanlevel'=>'clanLevel',
        'membernum'=>'memberNum','guildname'=>'guildName','timeonline'=>'timeOnline','timeactive'=>'timeActive','battletag'=>'battleTag',
        'socialtag'=>'socialTag','modeprefer'=>'modePrefer','rankshow'=>'rankShow','signaturebanexpiretime'=>'signatureBanExpireTime',
        'leaderboardtitles'=>'leaderboardTitles','selecteditemslots'=>'selectedItemSlots','equippeditems'=>'equippedItems',
        'nickname'=>'nickname','level'=>'level','exp'=>'exp','region'=>'region','liked'=>'liked','signature'=>'signature',
        'gender'=>'gender','language'=>'language','title'=>'title','capacity'=>'capacity'
    ];

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function normalize(array $data): array
    {
        $out=[];
        foreach($data as $key=>$value){
            $normalizedKey=self::KEYS[strtolower((string)$key)]??(string)$key;
            if(is_array($value)){
                $isList=array_is_list($value);
                $value=$isList?array_map(fn($item)=>is_array($item)?$this->normalize($item):$item,$value):$this->normalize($value);
            }
            $out[$normalizedKey]=$value;
        }
        return $out;
    }
}
