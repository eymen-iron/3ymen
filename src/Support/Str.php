<?php

declare(strict_types=1);

namespace Eymen\Support;

/**
 * String utility class providing static helper methods.
 *
 * All methods operate on UTF-8 strings using mbstring functions
 * where applicable. No external dependencies.
 */
final class Str
{
    /** @var array<string, string> Cache for studly conversions */
    private static array $studlyCache = [];

    /** @var array<string, string> Cache for camel conversions */
    private static array $camelCache = [];

    /** @var array<string, string> Cache for snake conversions */
    private static array $snakeCache = [];

    /**
     * Convert a string to camelCase.
     */
    public static function camel(string $value): string
    {
        if (isset(self::$camelCache[$value])) {
            return self::$camelCache[$value];
        }

        return self::$camelCache[$value] = lcfirst(self::studly($value));
    }

    /**
     * Convert a string to snake_case.
     */
    public static function snake(string $value, string $delimiter = '_'): string
    {
        $cacheKey = $value . $delimiter;

        if (isset(self::$snakeCache[$cacheKey])) {
            return self::$snakeCache[$cacheKey];
        }

        if (!ctype_lower($value)) {
            // Insert delimiter before uppercase letters
            $value = preg_replace('/\s+/u', '', ucwords($value)) ?? $value;
            $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value) ?? $value;
            $value = mb_strtolower($value, 'UTF-8');
        }

        return self::$snakeCache[$cacheKey] = $value;
    }

    /**
     * Convert a string to StudlyCase (PascalCase).
     */
    public static function studly(string $value): string
    {
        if (isset(self::$studlyCache[$value])) {
            return self::$studlyCache[$value];
        }

        $words = explode(' ', str_replace(['-', '_'], ' ', $value));

        $studly = implode('', array_map(
            fn(string $word): string => self::ucfirst($word),
            $words
        ));

        return self::$studlyCache[$value] = $studly;
    }

    /**
     * Convert a string to kebab-case.
     */
    public static function kebab(string $value): string
    {
        return self::snake($value, '-');
    }

    /**
     * Generate a URL-friendly "slug" from the given string.
     */
    public static function slug(string $value, string $separator = '-', string $language = 'en'): string
    {
        $value = self::ascii($value, $language);

        // Replace non-alphanumeric characters with separator
        $value = preg_replace('/[^a-zA-Z0-9\s]/', $separator, $value) ?? $value;

        // Replace whitespace with separator
        $value = preg_replace('/[\s]+/u', $separator, $value) ?? $value;

        // Remove consecutive separators
        $value = preg_replace('/[' . preg_quote($separator, '/') . ']{2,}/', $separator, $value) ?? $value;

        return trim(mb_strtolower($value, 'UTF-8'), $separator);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack The string to search in
     * @param string|array<string> $needles Substring(s) to search for
     */
    public static function contains(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param string $haystack The string to check
     * @param string|array<string> $needles Prefix(es) to check
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string $haystack The string to check
     * @param string|array<string> $needles Suffix(es) to check
     */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_ends_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the remainder of a string after the first occurrence of a given value.
     */
    public static function after(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $pos = strpos($subject, $search);

        return $pos === false ? $subject : substr($subject, $pos + strlen($search));
    }

    /**
     * Return the portion of a string before the first occurrence of a given value.
     */
    public static function before(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $pos = strpos($subject, $search);

        return $pos === false ? $subject : substr($subject, 0, $pos);
    }

    /**
     * Get the portion of a string between two given values.
     */
    public static function between(string $subject, string $from, string $to): string
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return self::before(self::after($subject, $from), $to);
    }

    /**
     * Limit the number of characters in a string.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }

    /**
     * Limit the number of words in a string.
     */
    public static function words(string $value, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $value, $matches);

        if (!isset($matches[0]) || mb_strlen($value) === mb_strlen($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]) . $end;
    }

    /**
     * Generate a random alphanumeric string.
     *
     * @throws \Exception If random bytes cannot be generated
     */
    public static function random(int $length = 16): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($characters) - 1;
        $result = '';

        $bytes = random_bytes($length);

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[ord($bytes[$i]) % ($max + 1)];
        }

        return $result;
    }

    /**
     * Convert the given string to upper-case.
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert the given string to lower-case.
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Convert the given string to title case.
     */
    public static function title(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Return the length of the given string.
     */
    public static function length(string $value, ?string $encoding = null): int
    {
        return mb_strlen($value, $encoding ?? 'UTF-8');
    }

    /**
     * Return a part of the string.
     */
    public static function substr(string $string, int $start, ?int $length = null): string
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    /**
     * Replace occurrences of the search string with the replacement string.
     *
     * @param string|array<string> $search Search string(s)
     * @param string|array<string> $replace Replacement string(s)
     * @param string $subject The subject string
     */
    public static function replace(string|array $search, string|array $replace, string $subject): string
    {
        return str_replace($search, $replace, $subject);
    }

    /**
     * Trim whitespace (or specific characters) from both sides of a string.
     */
    public static function trim(string $value, ?string $characters = null): string
    {
        return $characters !== null ? trim($value, $characters) : trim($value);
    }

    /**
     * Trim whitespace (or specific characters) from the left side of a string.
     */
    public static function ltrim(string $value, ?string $characters = null): string
    {
        return $characters !== null ? ltrim($value, $characters) : ltrim($value);
    }

    /**
     * Trim whitespace (or specific characters) from the right side of a string.
     */
    public static function rtrim(string $value, ?string $characters = null): string
    {
        return $characters !== null ? rtrim($value, $characters) : rtrim($value);
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param string|array<string> $pattern Pattern(s) with * as wildcard
     * @param string $value The value to match
     */
    public static function is(string|array $pattern, string $value): bool
    {
        foreach ((array) $pattern as $p) {
            if ($p === $value) {
                return true;
            }

            // Escape regex special characters except *
            $p = preg_quote($p, '#');

            // Replace escaped * with regex wildcard
            $p = str_replace('\*', '.*', $p);

            if (preg_match('#^' . $p . '\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a UUID (v4).
     */
    public static function uuid(): string
    {
        $bytes = random_bytes(16);

        // Set version to 4 (0100)
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        // Set variant to RFC 4122 (10xx)
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return sprintf(
            '%08s-%04s-%04s-%04s-%012s',
            bin2hex(substr($bytes, 0, 4)),
            bin2hex(substr($bytes, 4, 2)),
            bin2hex(substr($bytes, 6, 2)),
            bin2hex(substr($bytes, 8, 2)),
            bin2hex(substr($bytes, 10, 6))
        );
    }

    /**
     * Transliterate a string to its closest ASCII representation.
     */
    public static function ascii(string $value, string $language = 'en'): string
    {
        $charMap = self::getAsciiMap($language);

        $value = strtr($value, $charMap);

        // Remove remaining non-ASCII characters
        $value = preg_replace('/[^\x20-\x7E]/', '', $value) ?? $value;

        return $value;
    }

    /**
     * Get a very simple English pluralization of a word.
     *
     * Note: This is a simplified implementation covering common English rules.
     * For full inflection support, consider a dedicated inflector package.
     */
    public static function plural(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $lower = mb_strtolower($value);

        // Uncountable words
        $uncountable = [
            'audio', 'bison', 'cattle', 'chassis', 'compensation', 'coreopsis',
            'data', 'deer', 'education', 'emoji', 'equipment', 'evidence',
            'feedback', 'firmware', 'fish', 'furniture', 'gold', 'hardware',
            'information', 'jedi', 'knowledge', 'love', 'metadata', 'money',
            'moose', 'news', 'nutrition', 'offspring', 'plankton', 'pokemon',
            'police', 'rain', 'rice', 'series', 'sheep', 'software', 'species',
            'swine', 'traffic', 'wheat',
        ];

        if (in_array($lower, $uncountable, true)) {
            return $value;
        }

        // Irregular plurals
        $irregulars = [
            'child' => 'children', 'goose' => 'geese', 'man' => 'men',
            'woman' => 'women', 'tooth' => 'teeth', 'foot' => 'feet',
            'mouse' => 'mice', 'person' => 'people', 'ox' => 'oxen',
            'die' => 'dice', 'quiz' => 'quizzes', 'leaf' => 'leaves',
            'knife' => 'knives', 'life' => 'lives', 'wife' => 'wives',
            'half' => 'halves', 'self' => 'selves', 'calf' => 'calves',
            'wolf' => 'wolves', 'shelf' => 'shelves',
        ];

        if (isset($irregulars[$lower])) {
            return self::matchCase($irregulars[$lower], $value);
        }

        // Rules in order of priority
        $rules = [
            '/(quiz)$/i' => '$1zes',
            '/^(oxen)$/i' => '$1',
            '/([m|l])ouse$/i' => '$1ice',
            '/(matr|vert|append)ix$/i' => '$1ices',
            '/(x|ch|ss|sh)$/i' => '$1es',
            '/([^aeiouy]|qu)y$/i' => '$1ies',
            '/(hive)$/i' => '$1s',
            '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
            '/sis$/i' => 'ses',
            '/([ti])um$/i' => '$1a',
            '/(buffal|tomat|potat|ech|her|vet)o$/i' => '$1oes',
            '/(bus)$/i' => '$1es',
            '/(alias|status)$/i' => '$1es',
            '/(octop|vir)us$/i' => '$1i',
            '/(ax|test)is$/i' => '$1es',
            '/s$/i' => 's',
            '/$/' => 's',
        ];

        foreach ($rules as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $value);

            if ($result !== null && $result !== $value) {
                return $result;
            }
        }

        return $value . 's';
    }

    /**
     * Get a very simple English singularization of a word.
     *
     * Note: This is a simplified implementation covering common English rules.
     */
    public static function singular(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $lower = mb_strtolower($value);

        // Uncountable
        $uncountable = [
            'audio', 'bison', 'cattle', 'chassis', 'compensation', 'coreopsis',
            'data', 'deer', 'education', 'emoji', 'equipment', 'evidence',
            'feedback', 'firmware', 'fish', 'furniture', 'gold', 'hardware',
            'information', 'jedi', 'knowledge', 'love', 'metadata', 'money',
            'moose', 'news', 'nutrition', 'offspring', 'plankton', 'pokemon',
            'police', 'rain', 'rice', 'series', 'sheep', 'software', 'species',
            'swine', 'traffic', 'wheat',
        ];

        if (in_array($lower, $uncountable, true)) {
            return $value;
        }

        // Irregular singulars (reverse of plurals)
        $irregulars = [
            'children' => 'child', 'geese' => 'goose', 'men' => 'man',
            'women' => 'woman', 'teeth' => 'tooth', 'feet' => 'foot',
            'mice' => 'mouse', 'people' => 'person', 'oxen' => 'ox',
            'dice' => 'die', 'quizzes' => 'quiz', 'leaves' => 'leaf',
            'knives' => 'knife', 'lives' => 'life', 'wives' => 'wife',
            'halves' => 'half', 'selves' => 'self', 'calves' => 'calf',
            'wolves' => 'wolf', 'shelves' => 'shelf',
        ];

        if (isset($irregulars[$lower])) {
            return self::matchCase($irregulars[$lower], $value);
        }

        // Rules in order of priority
        $rules = [
            '/(database)s$/i' => '$1',
            '/(quiz)zes$/i' => '$1',
            '/(matr)ices$/i' => '$1ix',
            '/(vert|append)ices$/i' => '$1ix',
            '/^(ox)en/i' => '$1',
            '/(alias|status)es$/i' => '$1',
            '/(octop|vir)i$/i' => '$1us',
            '/(cris|ax|test)es$/i' => '$1is',
            '/(shoe)s$/i' => '$1',
            '/(o)es$/i' => '$1',
            '/(bus)es$/i' => '$1',
            '/([m|l])ice$/i' => '$1ouse',
            '/(x|ch|ss|sh)es$/i' => '$1',
            '/(m)ovies$/i' => '$1ovie',
            '/(s)eries$/i' => '$1eries',
            '/([^aeiouy]|qu)ies$/i' => '$1y',
            '/([lr])ves$/i' => '$1f',
            '/(tive)s$/i' => '$1',
            '/(hive)s$/i' => '$1',
            '/([^f])ves$/i' => '$1fe',
            '/(t)he(sis|ses)$/i' => '$1hesis',
            '/(synop|cri)(sis|ses)$/i' => '$1sis',
            '/(analy|ba|diagno|parenthe|progno|synop|the)(sis|ses)$/i' => '$1sis',
            '/([ti])a$/i' => '$1um',
            '/(n)ews$/i' => '$1ews',
            '/s$/i' => '',
        ];

        foreach ($rules as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $value);

            if ($result !== null && $result !== $value) {
                return $result;
            }
        }

        return $value;
    }

    /**
     * Determine if the given string is empty.
     */
    public static function isEmpty(?string $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * Determine if the given string is not empty.
     */
    public static function isNotEmpty(?string $value): bool
    {
        return !self::isEmpty($value);
    }

    /**
     * Cap a string with a single instance of a given value.
     */
    public static function finish(string $value, string $cap): string
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:' . $quoted . ')+$/u', '', $value) . $cap;
    }

    /**
     * Begin a string with a single instance of a given value.
     */
    public static function start(string $value, string $prefix): string
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix . preg_replace('/^(?:' . $quoted . ')+/u', '', $value);
    }

    /**
     * Pad the left side of a string to a given length.
     */
    public static function padLeft(string $value, int $length, string $pad = ' '): string
    {
        $short = max(0, $length - mb_strlen($value));

        return mb_substr(str_repeat($pad, (int) ceil($short / mb_strlen($pad))), 0, $short) . $value;
    }

    /**
     * Pad the right side of a string to a given length.
     */
    public static function padRight(string $value, int $length, string $pad = ' '): string
    {
        $short = max(0, $length - mb_strlen($value));

        return $value . mb_substr(str_repeat($pad, (int) ceil($short / mb_strlen($pad))), 0, $short);
    }

    /**
     * Wrap the string with the given strings.
     */
    public static function wrap(string $value, string $before, ?string $after = null): string
    {
        return $before . $value . ($after ?? $before);
    }

    /**
     * Unwrap the string with the given strings.
     */
    public static function unwrap(string $value, string $before, ?string $after = null): string
    {
        if (str_starts_with($value, $before)) {
            $value = substr($value, strlen($before));
        }

        $after ??= $before;

        if (str_ends_with($value, $after)) {
            $value = substr($value, 0, -strlen($after));
        }

        return $value;
    }

    /**
     * Mask a portion of a string with a repeated character.
     */
    public static function mask(string $string, string $character, int $index, ?int $length = null): string
    {
        if ($character === '') {
            return $string;
        }

        $strLength = mb_strlen($string);

        // Handle negative index
        if ($index < 0) {
            $index = max(0, $strLength + $index);
        }

        if ($index >= $strLength) {
            return $string;
        }

        $length ??= $strLength;
        $start = mb_substr($string, 0, $index);
        $maskLength = $length < 0 ? max(0, $strLength - $index + $length) : min($length, $strLength - $index);
        $end = mb_substr($string, $index + $maskLength);

        return $start . str_repeat($character[0], $maskLength) . $end;
    }

    /**
     * Make the first character uppercase.
     */
    public static function ucfirst(string $string): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /**
     * Make the first character lowercase.
     */
    public static function lcfirst(string $string): string
    {
        return mb_strtolower(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /**
     * Match a case pattern from a reference string to a target string.
     */
    private static function matchCase(string $value, string $reference): string
    {
        // All uppercase
        if (mb_strtoupper($reference) === $reference) {
            return mb_strtoupper($value);
        }

        // First letter uppercase
        if (mb_strtoupper(mb_substr($reference, 0, 1)) === mb_substr($reference, 0, 1)) {
            return self::ucfirst($value);
        }

        return $value;
    }

    /**
     * Get ASCII character map for transliteration.
     *
     * @return array<string, string>
     */
    private static function getAsciiMap(string $language): array
    {
        $default = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'AE', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',
            // Turkish
            'Ğ' => 'G', 'ğ' => 'g', 'İ' => 'I', 'ı' => 'i', 'Ş' => 'S', 'ş' => 's',
            // Polish
            'Ą' => 'A', 'ą' => 'a', 'Ć' => 'C', 'ć' => 'c', 'Ę' => 'E', 'ę' => 'e',
            'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ś' => 'S', 'ś' => 's',
            'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z',
            // Czech/Slovak
            'Č' => 'C', 'č' => 'c', 'Ď' => 'D', 'ď' => 'd', 'Ě' => 'E', 'ě' => 'e',
            'Ň' => 'N', 'ň' => 'n', 'Ř' => 'R', 'ř' => 'r', 'Š' => 'S', 'š' => 's',
            'Ť' => 'T', 'ť' => 't', 'Ů' => 'U', 'ů' => 'u', 'Ž' => 'Z', 'ž' => 'z',
            // Romanian
            'Ă' => 'A', 'ă' => 'a', 'Ș' => 'S', 'ș' => 's', 'Ț' => 'T', 'ț' => 't',
            // Other common
            'Đ' => 'D', 'đ' => 'd', 'Œ' => 'OE', 'œ' => 'oe',
            '€' => 'EUR', '£' => 'GBP', '¥' => 'JPY',
            '©' => '(c)', '®' => '(r)', '™' => '(tm)',
            // Typographic
            "\xE2\x80\x9C" => '"', "\xE2\x80\x9D" => '"',
            "\xE2\x80\x98" => "'", "\xE2\x80\x99" => "'",
            "\xE2\x80\x93" => '-', "\xE2\x80\x94" => '-',
            "\xE2\x80\xA6" => '...',
        ];

        return match ($language) {
            'tr' => array_merge($default, ['Ğ' => 'G', 'ğ' => 'g', 'İ' => 'I', 'ı' => 'i', 'Ş' => 'S', 'ş' => 's']),
            'de' => array_merge($default, ['Ä' => 'Ae', 'ä' => 'ae', 'Ö' => 'Oe', 'ö' => 'oe', 'Ü' => 'Ue', 'ü' => 'ue']),
            default => $default,
        };
    }
}
