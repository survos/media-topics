<?php

declare(strict_types=1);

namespace Survos\MediaTopics;

/**
 * One concept from an IPTC NewsCodes vocabulary. The id is the identity; labels are presentation.
 */
abstract readonly class Concept
{
    /** e.g. "http://cv.iptc.org/newscodes/genre/" */
    abstract public static function schemeUri(): string;

    /** e.g. "genre:" */
    abstract public static function qcodePrefix(): string;

    /**
     * @param array<string, string> $labels      locale => label
     * @param array<string, string> $definitions locale => definition
     */
    public function __construct(
        public string $id,
        public array $labels,
        public array $definitions = [],
        public ?\DateTimeImmutable $retired = null,
        public ?\DateTimeImmutable $created = null,
        public ?\DateTimeImmutable $modified = null,
    ) {}

    public function qcode(): string
    {
        return static::qcodePrefix().$this->id;
    }

    public function uri(): string
    {
        return static::schemeUri().$this->id;
    }

    public function isRetired(): bool
    {
        return $this->retired !== null;
    }

    /** The label in the locale, else in English; never empty for a published concept. */
    public function label(string $locale = ConceptSet::DEFAULT_LOCALE): string
    {
        return $this->labels[$locale] ?? $this->labels['en-GB'] ?? $this->labels['en-US'] ?? $this->id;
    }

    public function definition(string $locale = ConceptSet::DEFAULT_LOCALE): string
    {
        return $this->definitions[$locale] ?? $this->definitions['en-GB'] ?? $this->definitions['en-US'] ?? '';
    }
}
