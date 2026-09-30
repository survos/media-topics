# survos/media-topics

[IPTC Media Topics](https://iptc.org/standards/media-topics/) as a PHP library: the subject
vocabulary news and media organizations use to say what a piece of content is about. About 1,100
topics in use, under 17 top-level subjects, up to five levels deep.

It also carries [IPTC Genre](https://cv.iptc.org/newscodes/genre/): what kind of item something
is (Obituary, Interview, Opinion, Review, Press Release, ...), as opposed to what it is about.

The package ships a pinned copy of IPTC's own export (release 2026-07-02, 13 locales) and reads it
into plain objects. No framework, no database, no network at runtime. For Symfony services and
commands, see `survos/media-topics-bundle`.

Media Topics is the maintained successor to the older IPTC Subject Codes; each topic carries the
Subject Code and Wikidata item it corresponds to, where IPTC has mapped one.

```bash
composer require survos/media-topics
```

## Use

```php
use Survos\MediaTopics\MediaTopics;

$topics = MediaTopics::load();          // the pinned release
$topics->version();                     // "2026-07-02"

$topic = $topics->get('medtop:20000479');   // id, qcode or URI all resolve
$topic->id;                             // "20000479": store this (or qcode()/uri()), never the label
$topic->label('en-US');                 // "healthcare policy"
$topic->label('es');                    // "Política de atención de salud"
$topic->definition();
$topic->wikidata;                       // ["Q1519812"]
$topic->subjectCodes;                   // ["07013000"]

$topics->roots();                       // the 17 top-level topics
$topics->children('11000000');
$topics->ancestors('20000479');         // root first: politics and government > government policy
$topics->descendants('11000000');
$topics->search('healthcare policy');   // by words in label and definition
foreach ($topics as $id => $topic) { }  // every topic in use, parents before children
```

Store only the topic you assigned. Its ancestors come from the vocabulary, so a search or facet at
any level still finds the item.

## Genres

```php
use Survos\MediaTopics\Genres;

$genres = Genres::load();               // release 2024-02-13: 51 genres in use, English only, flat
$genre = $genres->get('genre:Obituary');
$genre->id;                             // "Obituary"
$genre->definition();                   // "A narrative about an individual's life and achievements ..."
$genres->search('opinion');
foreach ($genres as $id => $genre) { }
```

`Genres` and `MediaTopics` share `get()`, `all()`, `search()`, `version()`, `locales()`, iteration
and counting; `MediaTopics` adds the hierarchy.

## Retired topics

IPTC retires topics but never reuses their ids. Retired topics still resolve through `get()`,
because stored classifications may point at them, and are left out of `roots()`, `children()`,
`all()`, iteration and `search()`. Pass `includeRetired: true` to see them; `$topic->retired` holds
the date.

## Another release

```php
$topics = MediaTopics::load('/path/to/export.json');
```

The file is IPTC's export from
`https://cv.iptc.org/newscodes/mediatopic/?format=json&lang=x-all`.

## License

The code is MIT. The vocabularies in `resources/` are © IPTC, International Press
Telecommunications Council, licensed [CC BY 4.0](https://creativecommons.org/licenses/by/4.0/).
Credit IPTC in applications that embed it.
