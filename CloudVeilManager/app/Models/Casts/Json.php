<?php

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Json implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_decode($value, true);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $this->castIntegers($value);
        return json_encode($value);
    }

    private function castIntegers(mixed &$values) {

        foreach ($values as $key=>&$value) {
            if(is_array($value) || is_object($value)) {
                $this->castIntegers($value);
            } else if(is_numeric($value)) {
                $intV = (int)$value;
                if((string)$intV === $value) {
                    //TODO: Change Time Restrictions representation
                    //THIS IS A HACK.
                    //only casting integers interpreted as integers. Ignoring float values so "2.00" would remain strings.
                    //because TimeRestrictions field is hacky and time represented with dot like "23.42"
                    //but other counters like Bypasses should be integers
                    $value = (int)$value;
                }
            }
        }
    }
}
