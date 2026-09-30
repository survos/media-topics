<?php

declare(strict_types=1);

namespace Survos\MediaTopics;

/** One IPTC Media Topic: what an item is about. The id ("20000479") is the identity. */
final readonly class MediaTopic extends Concept
{
    /**
     * @param array<string, string> $labels       locale => label
     * @param array<string, string> $definitions  locale => definition
     * @param list<string>          $subjectCodes legacy IPTC Subject Codes this topic corresponds to ("07013000")
     * @param list<string>          $wikidata     Wikidata items this topic corresponds to ("Q1519812")
     */
    public function __construct(
        string $id,
        array $labels,
        array $definitions = [],
        public ?string $parentId = null,
        ?\DateTimeImmutable $retired = null,
        ?\DateTimeImmutable $created = null,
        ?\DateTimeImmutable $modified = null,
        public array $subjectCodes = [],
        public array $wikidata = [],
    ) {
        parent::__construct($id, $labels, $definitions, $retired, $created, $modified);
    }

    public static function schemeUri(): string
    {
        return 'http://cv.iptc.org/newscodes/mediatopic/';
    }

    public static function qcodePrefix(): string
    {
        return 'medtop:';
    }
}
