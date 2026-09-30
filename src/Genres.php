<?php

declare(strict_types=1);

namespace Survos\MediaTopics;

/**
 * The IPTC Genre vocabulary (https://cv.iptc.org/newscodes/genre/): the nature of an item, such as
 * Obituary, Interview, Opinion or Press Release. A flat list; ids are words ("Obituary").
 *
 * @extends ConceptSet<Genre>
 */
final class Genres extends ConceptSet
{
    public const string PINNED_FILE = __DIR__.'/../resources/genre.json';

    /** Reads IPTC's export: https://cv.iptc.org/newscodes/genre/?format=json&lang=x-all */
    public static function load(string $file = self::PINNED_FILE): self
    {
        [$rows, $released] = self::read($file);

        return new self(array_map(static fn (array $row): Genre => new Genre(
            self::id($row['qcode']),
            $row['prefLabel'] ?? [],
            $row['definition'] ?? [],
            self::date($row['retired'] ?? null),
            self::date($row['created'] ?? null),
            self::date($row['modified'] ?? null),
        ), $rows), $released);
    }
}
