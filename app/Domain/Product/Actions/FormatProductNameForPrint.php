<?php

namespace App\Domain\Product\Actions;

class FormatProductNameForPrint
{
   
    public function handle(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }

        $formatted = preg_replace('/(\b\d+(?:\.\d+)?)\s+mm\b/i', '$1&nbsp;mm', $trimmed);

        $formatted = preg_replace('/(\b\d+)\s+Holes\b/i', '$1&nbsp;Holes', $formatted);

        $formatted = preg_replace('/(\bHoles)\s+(\d+\b)/i', '$1&nbsp;$2', $formatted);

        $formatted = preg_replace('/\s+-\s+(Left|Right|Straight|Curved)\b/i', '&nbsp;-&nbsp;$1', $formatted);
        if (strlen($trimmed) > 22) {
            $patternHolesEnd = '/\s+((?:\d+&nbsp;Holes|Holes&nbsp;\d+)(?:&nbsp;-&nbsp;(?:Left|Right|Straight|Curved))?)$/i';
            if (preg_match($patternHolesEnd, $formatted)) {
                $formatted = preg_replace($patternHolesEnd, '<br>$1', $formatted, 1);
            } elseif (preg_match('/\s+(&nbsp;-&nbsp;(?:Left|Right|Straight|Curved))$/i', $formatted)) {
                $formatted = preg_replace('/\s+(&nbsp;-&nbsp;(?:Left|Right|Straight|Curved))$/i', '<br>$1', $formatted, 1);
            }
        }

        return $formatted;
    }
}
