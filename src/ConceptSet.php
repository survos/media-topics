<?php

declare(strict_types=1);

namespace Survos\MediaTopics;

/**
 * An IPTC NewsCodes vocabulary read from a pinned copy of IPTC's own JSON export
 * (© IPTC, CC BY 4.0). Concepts are addressed by id, qcode or URI; all three resolve.
 *
 * Retired concepts stay resolvable through get(), because stored classifications may point at
 * them, but are left out of all(), iteration, count() and search() unless asked for.
 *
 * @template T of Concept
 * @implements \IteratorAggregate<string, T>
 */
abstract class ConceptSet implements \IteratorAggregate, \Countable
{
    public const string DEFAULT_LOCALE = 'en-GB';

    /** @var array<string, T> */
    protected array $concepts = [];

    /** @param iterable<T> $concepts */
    public function __construct(iterable $concepts, public readonly ?\DateTimeImmutable $released = null)
    {
        foreach ($concepts as $concept) {
            if (isset($this->concepts[$concept->id])) {
                throw new \InvalidArgumentException('Duplicate concept '.$concept->qcode());
            }
            $this->concepts[$concept->id] = $concept;
        }
    }

    /** The release date of the loaded vocabulary ("2026-07-02"); store it with every classification. */
    public function version(): ?string
    {
        return $this->released?->format('Y-m-d');
    }

    /**
     * @param string $reference id, qcode or URI
     * @return T|null
     */
    public function get(string $reference): ?Concept
    {
        return $this->concepts[self::id($reference)] ?? null;
    }

    /** @return list<T> */
    public function all(bool $includeRetired = false): array
    {
        return array_values($includeRetired ? $this->concepts : array_filter($this->concepts, static fn (Concept $c): bool => !$c->isRetired()));
    }

    /**
     * Plain word matching over labels and definitions: good enough to look a concept up by name;
     * not a substitute for a search engine on long text.
     *
     * @return list<T> best match first
     */
    public function search(string $query, string $locale = self::DEFAULT_LOCALE, int $limit = 20): array
    {
        $words = self::words($query);
        if ($words === []) {
            return [];
        }
        $scores = [];
        foreach ($this->all() as $concept) {
            $label = self::words($concept->label($locale));
            $definition = self::words($concept->definition($locale));
            $score = 0.0;
            foreach ($words as $word) {
                $score += (in_array($word, $label, true) ? 3 : 0) + (in_array($word, $definition, true) ? 1 : 0);
            }
            if ($score > 0) {
                // Prefer the concept whose label is mostly the query over one that merely contains it.
                $scores[$concept->id] = $score + count(array_intersect($label, $words)) / max(count($label), 1);
            }
        }
        arsort($scores);

        return array_map(fn (string|int $id): Concept => $this->concepts[(string) $id], array_slice(array_keys($scores), 0, $limit));
    }

    /** @return list<string> locales that have labels, e.g. en-GB, fr, zh-Hans */
    public function locales(): array
    {
        $locales = [];
        foreach ($this->concepts as $concept) {
            $locales += array_fill_keys(array_keys($concept->labels), true);
        }
        ksort($locales);

        return array_keys($locales);
    }

    /** Number of concepts in use (retired ones excluded). */
    public function count(): int
    {
        return count($this->all());
    }

    /** @return \ArrayIterator<string, T> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator(array_column($this->all(), null, 'id'));
    }

    /**
     * @return array{list<array<string, mixed>>, ?\DateTimeImmutable} the export's concept rows and release date
     */
    protected static function read(string $file): array
    {
        $data = json_decode((string) file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);

        return [$data['conceptSet'], self::date($data['dateReleased'] ?? null)];
    }

    /** "medtop:07000000", ".../mediatopic/07000000" and "07000000" all give "07000000". */
    protected static function id(string $reference): string
    {
        return substr($reference, (int) max(strrpos($reference, ':'), strrpos($reference, '/'), -1) + 1);
    }

    protected static function date(?string $value): ?\DateTimeImmutable
    {
        return $value === null ? null : new \DateTimeImmutable($value);
    }

    /** @return list<string> */
    private static function words(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($words, static fn (string $w): bool => mb_strlen($w) > 2));
    }
}
