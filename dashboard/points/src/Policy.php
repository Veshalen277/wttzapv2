<?php
namespace Participation;
final class Policy
{
    public static function award(array $rule,int $used,?string $date,int $length,bool $eligible=true): array
    {
        if (!$eligible) return [0,'Ineligible: self-reaction or target cannot be verified'];
        if (!$date) return [0,'Unscored: reliable activity timestamp unavailable'];
        if ($rule['points']===0) return [0,'Tracked only: not an earned contribution'];
        if ($length<$rule['min']) return [0,'Below published minimum text length'];
        if ($used + $rule['points'] > $rule['points']*$rule['limit']) return [0,'Daily limit reached'];
        return [$rule['points'],'Activity award'];
    }
    public static function date($value,string $timezone='Africa/Johannesburg'): ?string
    {
        if (!is_string($value)) return null;
        $value=strlen($value)===10?$value.' 00:00:00':$value;
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new \DateTimeZone($timezone));
        if (!$date || $date->format('Y-m-d H:i:s')!==$value || $date>new \DateTimeImmutable('now')) return null;
        return $date->setTimezone(new \DateTimeZone('Africa/Johannesburg'))->format('Y-m-d H:i:s');
    }
}
