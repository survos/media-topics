<?php

declare(strict_types=1);

namespace Survos\MediaTopics;

/**
 * The IPTC Media Topics vocabulary (https://iptc.org/standards/media-topics/): what an item is
 * about. A tree under 17 top-level subjects; ids are numbers ("20000479").
 *
 * roots(), children(), descendants() and all() leave retired topics out unless asked for.
 *
 * @extends ConceptSet<MediaTopic>
 */
final class MediaTopics extends ConceptSet
{
    public const string PINNED_FILE = __DIR__.'/../resources/mediatopic.json';

    /** @var array<string, list<string>> parent id ('' for roots) => child ids, retired included */
    private array $childIds = [];

    /** @param iterable<MediaTopic> $topics */
    public function __construct(iterable $topics, ?\DateTimeImmutable $released = null)
    {
        parent::__construct($topics, $released);
        foreach ($this->concepts as $topic) {
            if ($topic->parentId !== null && !isset($this->concepts[$topic->parentId])) {
                throw new \InvalidArgumentException(sprintf('Media Topic %s has unknown parent %s.', $topic->id, $topic->parentId));
            }
            $this->childIds[$topic->parentId ?? ''][] = $topic->id;
        }
        foreach ($this->concepts as $topic) {
            $this->ancestors($topic->id); // throws on a cycle
        }
    }

    /** Reads IPTC's export: https://cv.iptc.org/newscodes/mediatopic/?format=json&lang=x-all */
    public static function load(string $file = self::PINNED_FILE): self
    {
        [$rows, $released] = self::read($file);
        $topics = [];
        foreach ($rows as $row) {
            if (count($row['broader'] ?? []) > 1) {
                throw new \RuntimeException('Media Topic with several parents: '.$row['qcode']);
            }
            $subjectCodes = $wikidata = [];
            foreach ([...($row['exactMatch'] ?? []), ...($row['closeMatch'] ?? [])] as $match) {
                if (str_contains($match, '/newscodes/subjectcode/')) {
                    $subjectCodes[] = self::id($match);
                } elseif (str_contains($match, 'wikidata.org/')) {
                    $wikidata[] = self::id($match);
                }
            }
            $topics[] = new MediaTopic(
                self::id($row['qcode']),
                $row['prefLabel'] ?? [],
                $row['definition'] ?? [],
                isset($row['broader'][0]) ? self::id($row['broader'][0]) : null,
                self::date($row['retired'] ?? null),
                self::date($row['created'] ?? null),
                self::date($row['modified'] ?? null),
                array_values(array_unique($subjectCodes)),
                array_values(array_unique($wikidata)),
            );
        }

        return new self($topics, $released);
    }

    /** @return list<MediaTopic> */
    public function roots(bool $includeRetired = false): array
    {
        return $this->childrenOf('', $includeRetired);
    }

    /** @return list<MediaTopic> */
    public function children(string $reference, bool $includeRetired = false): array
    {
        return $this->childrenOf(self::id($reference), $includeRetired);
    }

    /** @return list<MediaTopic> from the root down to the topic's parent */
    public function ancestors(string $reference): array
    {
        $ancestors = [];
        $seen = [];
        for ($topic = $this->get($reference); $topic?->parentId !== null; $topic = $this->concepts[$topic->parentId]) {
            if (isset($seen[$topic->id])) {
                throw new \InvalidArgumentException('Cycle in the Media Topics hierarchy at '.$topic->id);
            }
            $seen[$topic->id] = true;
            array_unshift($ancestors, $this->concepts[$topic->parentId]);
        }

        return $ancestors;
    }

    /** 0 for a top-level topic. */
    public function depth(string $reference): int
    {
        return count($this->ancestors($reference));
    }

    /** @return list<MediaTopic> the topic and everything under it */
    public function descendants(string $reference, bool $includeRetired = false): array
    {
        $found = [];
        foreach ($this->children($reference, $includeRetired) as $child) {
            array_push($found, $child, ...$this->descendants($child->id, $includeRetired));
        }

        return $found;
    }

    /** @return list<MediaTopic> parents always before their children, so rows can be inserted in order */
    public function all(bool $includeRetired = false): array
    {
        $ordered = [];
        foreach ($this->roots($includeRetired) as $root) {
            array_push($ordered, $root, ...$this->descendants($root->id, $includeRetired));
        }

        return $ordered;
    }

    /** @return list<MediaTopic> */
    private function childrenOf(string $parentId, bool $includeRetired): array
    {
        $children = array_map(fn (string $id): MediaTopic => $this->concepts[$id], $this->childIds[$parentId] ?? []);

        return $includeRetired ? $children : array_values(array_filter($children, static fn (MediaTopic $t): bool => !$t->isRetired()));
    }
}
