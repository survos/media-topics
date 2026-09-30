<?php

declare(strict_types=1);

namespace Survos\MediaTopics\Tests;

use PHPUnit\Framework\TestCase;
use Survos\MediaTopics\Genres;
use Survos\MediaTopics\MediaTopic;
use Survos\MediaTopics\MediaTopics;

final class MediaTopicsTest extends TestCase
{
    private static MediaTopics $topics;

    public static function setUpBeforeClass(): void
    {
        self::$topics = MediaTopics::load();
    }

    public function testPinnedReleaseLoads(): void
    {
        self::assertSame('2026-07-02', self::$topics->version());
        self::assertCount(17, self::$topics->roots());
        self::assertCount(1085, self::$topics);
        self::assertCount(1393, self::$topics->all(includeRetired: true));
        self::assertContains('fr', self::$topics->locales());
    }

    public function testATopicResolvesByIdQcodeOrUri(): void
    {
        $topic = self::$topics->get('20000479');
        self::assertNotNull($topic);
        self::assertSame($topic, self::$topics->get('medtop:20000479'));
        self::assertSame($topic, self::$topics->get('http://cv.iptc.org/newscodes/mediatopic/20000479'));
        self::assertSame('medtop:20000479', $topic->qcode());
        self::assertSame('http://cv.iptc.org/newscodes/mediatopic/20000479', $topic->uri());
        self::assertNull(self::$topics->get('nope'));
    }

    public function testLabelsDefinitionsAndMappings(): void
    {
        $topic = self::$topics->get('20000479');
        self::assertSame('healthcare policy', $topic->label('en-US'));
        self::assertSame('Política de atención de salud', $topic->label('es'));
        self::assertSame($topic->label('en-GB'), $topic->label('xx'));
        self::assertNotSame('', $topic->definition());
        self::assertSame(['07013000'], $topic->subjectCodes);
        self::assertSame(['Q1519812'], $topic->wikidata);
    }

    public function testHierarchy(): void
    {
        $ancestors = self::$topics->ancestors('20000479');
        self::assertSame(['11000000', '20000621'], [$ancestors[0]->id, end($ancestors)->id]);
        self::assertSame('politics and government', $ancestors[0]->label('en-US'));
        self::assertSame(count($ancestors), self::$topics->depth('20000479'));
        self::assertSame(0, self::$topics->depth('11000000'));
        self::assertContains('20000479', array_map(static fn (MediaTopic $t): string => $t->id, self::$topics->descendants('11000000')));

        $seen = [];
        foreach (self::$topics->all(includeRetired: true) as $topic) {
            self::assertTrue($topic->parentId === null || isset($seen[$topic->parentId]), 'parents come first');
            $seen[$topic->id] = true;
        }
    }

    public function testRetiredTopicsResolveButAreNotOffered(): void
    {
        $retired = array_values(array_filter(self::$topics->all(includeRetired: true), static fn (MediaTopic $t): bool => $t->isRetired()));
        self::assertCount(308, $retired);
        self::assertNotNull(self::$topics->get($retired[0]->id));
        foreach (self::$topics as $topic) {
            self::assertFalse($topic->isRetired());
        }
    }

    public function testSearchFindsATopicByName(): void
    {
        self::assertSame('20000479', self::$topics->search('healthcare policy', 'en-US')[0]->id);
        self::assertSame([], self::$topics->search('zzzzqqqq'));
        self::assertLessThanOrEqual(5, count(self::$topics->search('health', limit: 5)));
    }

    public function testGenresAreAFlatVocabularyWithWordIds(): void
    {
        $genres = Genres::load();
        self::assertSame('2024-02-13', $genres->version());
        self::assertCount(51, $genres);
        self::assertCount(58, $genres->all(includeRetired: true));

        $obituary = $genres->get('genre:Obituary');
        self::assertNotNull($obituary);
        self::assertSame($obituary, $genres->get('http://cv.iptc.org/newscodes/genre/Obituary'));
        self::assertSame(['Obituary', 'genre:Obituary', 'http://cv.iptc.org/newscodes/genre/Obituary'], [$obituary->id, $obituary->qcode(), $obituary->uri()]);
        self::assertStringContainsString('after death', $obituary->definition());
        self::assertSame('Obituary', $genres->search('obituary')[0]->id);
        self::assertSame(['en-GB'], $genres->locales());
    }
}
