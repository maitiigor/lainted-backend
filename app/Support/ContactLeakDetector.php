<?php

namespace App\Support;

use App\Support\Enums\ContactLeakPattern;

/**
 * Regex-based detector for contact info embedded in free text - the
 * product doc's anti-leakage requirement (listing text now, chat
 * messages later), not full NLP. Also normalizes a couple of common,
 * casual evasions (spelled-out digits, "dot"/"at" instead of "."/"@")
 * before matching - not meant to defeat a determined bad actor, just
 * catch the everyday attempts.
 */
class ContactLeakDetector
{
    private const WORD_DIGITS = [
        'zero' => '0', 'oh' => '0',
        'one' => '1', 'two' => '2', 'three' => '3', 'four' => '4',
        'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8', 'nine' => '9',
    ];

    /**
     * Returns the matched pattern + the matched substring, or null if
     * the text is clean.
     *
     * @return array{pattern: ContactLeakPattern, matched_text: string}|null
     */
    public static function detect(string $text): ?array
    {
        if ($match = self::matchPhone($text)) {
            return ['pattern' => ContactLeakPattern::Phone, 'matched_text' => $match];
        }

        $deobfuscated = self::deobfuscateLinks($text);

        if (preg_match('/(whatsapp|wa\s*\.?\s*me\s*\/?\S*)/i', $deobfuscated, $m)) {
            return ['pattern' => ContactLeakPattern::Whatsapp, 'matched_text' => trim($m[0])];
        }

        if (preg_match('/(telegram|t\s*\.?\s*me\s*\/?\S*)/i', $deobfuscated, $m)) {
            return ['pattern' => ContactLeakPattern::Telegram, 'matched_text' => trim($m[0])];
        }

        if (preg_match('/(instagram|\big\b)\s*[:@]?\s*@?[a-z0-9_.]{3,}/i', $deobfuscated, $m)) {
            return ['pattern' => ContactLeakPattern::Instagram, 'matched_text' => trim($m[0])];
        }

        if (preg_match('/[a-z0-9._%+-]+\s*@\s*[a-z0-9.-]+\s*\.\s*[a-z]{2,}/i', $deobfuscated, $m)) {
            return ['pattern' => ContactLeakPattern::Email, 'matched_text' => trim($m[0])];
        }

        return null;
    }

    /**
     * Nigerian mobile numbers: 11 digits starting with 0, or +234/234
     * followed by 10 digits - allowing spaces/dashes between groups so
     * "0816 056 3589" and "08160563589" both match. Also checks a
     * digit-word-normalized copy so "zero eight one six..." matches too.
     */
    private static function matchPhone(string $text): ?string
    {
        $pattern = '/(?:\+?234[\s\-]?\d{3}[\s\-]?\d{3}[\s\-]?\d{4}|0\d{2,3}[\s\-]?\d{3}[\s\-]?\d{4})/';

        if (preg_match($pattern, $text, $m)) {
            return $m[0];
        }

        $normalized = self::wordsToDigits($text);

        return preg_match($pattern, $normalized, $m) ? $m[0] : null;
    }

    /**
     * "zero eight one six..." needs to become one contiguous "0816..."
     * block, not digits still separated by spaces - the phone regex
     * expects a run of digits per group, not one digit per token.
     */
    private static function wordsToDigits(string $text): string
    {
        $words = preg_split('/\s+/', strtolower($text));
        $result = [];
        $digitBuffer = '';

        foreach ($words as $word) {
            if (isset(self::WORD_DIGITS[$word])) {
                $digitBuffer .= self::WORD_DIGITS[$word];

                continue;
            }

            if ($digitBuffer !== '') {
                $result[] = $digitBuffer;
                $digitBuffer = '';
            }

            $result[] = $word;
        }

        if ($digitBuffer !== '') {
            $result[] = $digitBuffer;
        }

        return implode(' ', $result);
    }

    /**
     * "wa dot me", "gmail dot com", "someone at gmail dot com" - spelling
     * out "." and "@" instead of typing them is a common, casual way to
     * dodge a naive literal-character regex. Unconditional word-for-symbol
     * replacement (no lookahead) so there's no ordering dependency between
     * the two substitutions.
     */
    private static function deobfuscateLinks(string $text): string
    {
        return preg_replace(['/\bdot\b/i', '/\bat\b/i'], ['.', '@'], $text);
    }
}
