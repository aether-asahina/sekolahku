<?php

namespace App\Support;

class Teks
{
    /** Hapus tag HTML dari kolom teks yang disebutkan (pertahanan lapis kedua terhadap XSS). */
    public static function bersih(array $data, array $kolom): array
    {
        foreach ($kolom as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $data[$k] = trim(strip_tags($data[$k]));
            }
        }
        return $data;
    }
}
