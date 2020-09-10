<?php
namespace Valorin\Pwned;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class Pwned implements Rule
{
    /** @var int */
    private $minimum;

    /**
     * @param int $minimum Minimum number of times the password was pwned before it is blocked
     */
    public function __construct($minimum = 1)
    {
        $this->minimum = $minimum;
    }

    public function validate($attribute, $value, $params)
    {
        $this->minimum = array_shift($params) ?? 1;

        return $this->passes($attribute, $value);
    }

    public function passes($attribute, $value)
    {
        list($prefix, $suffix) = $this->hashAndSplit($value);
        $results = $this->query($prefix);
        $count = $results[$suffix] ?? 0;

        return $count < $this->minimum;
    }

    public function message()
    {
        return Lang::get('validation.pwned');
    }

    private function hashAndSplit($value)
    {
        $hash = strtoupper(sha1($value));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        return [$prefix, $suffix];
    }

    private function query($prefix)
    {
        // Cache results for a week, to avoid constant API calls for identical prefixes
        return Cache::remember('pwned:'.$prefix, Carbon::now()->addWeek(), function () use ($prefix) {
            $curl = curl_init('https://api.pwnedpasswords.com/range/'.$prefix);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            //Set Add-Padding to true to pad queries. (See: https://haveibeenpwned.com/API/v3#PwnedPasswordsPadding)
            curl_setopt($curl, CURLOPT_HEADER, Add-Padding: false);
            $results = curl_exec($curl);
            curl_close($curl);

            $hashes = explode("\n", trim($results));

            return (new Collection($hashes))
                ->mapWithKeys(function ($value) {
                    $pair = explode(':', trim($value), 2);

                    return count($pair) === 2 && is_numeric($pair[1])
                        ? [$pair[0] => $pair[1]]
                        : [];
                });
        });
    }
}
