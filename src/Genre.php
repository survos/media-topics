<?php

declare(strict_types=1);

namespace Survos\MediaTopics;

/** One IPTC Genre: what kind of item something is ("Obituary", "Review"), not what it is about. */
final readonly class Genre extends Concept
{
    public static function schemeUri(): string
    {
        return 'http://cv.iptc.org/newscodes/genre/';
    }

    public static function qcodePrefix(): string
    {
        return 'genre:';
    }
}
