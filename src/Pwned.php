<?php
namespace Valorin\Pwned;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
        return trans()->has('validation.pwned') ? trans('validation.pwned') : 'Your password has been pwned!';
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
        return Cache::remember('pwned:'.$prefix, 10080, function () use ($prefix) {
            $results = file_get_contents('https://api.pwnedpasswords.com/range/'.$prefix);

            return (new Collection(explode("\n", $results)))
                ->mapWithKeys(function ($value) {
                    list($suffix, $count) = explode(':', trim($value));
                    return [$suffix => $count];
                });
        });
    }
}
