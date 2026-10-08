<?php
/**
 * Seeds demo content so a fresh site has every section type on screen.
 *
 *   ddev exec php scripts/seed-demo-content.php
 *
 * Creates placeholder images, a few people, testimonials, companies,
 * products and blog posts, the legal pages, and a home page that uses every
 * section. Safe to run once on an empty install; it does not check for
 * duplicates. Replace or delete the content afterwards — it is only there so
 * the client can see what each section looks like.
 */

require dirname(__DIR__) . '/bootstrap.php';

/** @var craft\console\Application $app */
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;

$elements = Craft::$app->getElements();
$entries = Craft::$app->getEntries();

$sectionId = [];
foreach ($entries->getAllSections() as $section) {
    $sectionId[$section->handle] = $section->id;
}

$typeId = [];
foreach ($entries->getAllEntryTypes() as $type) {
    $typeId[$type->handle] = $type->id;
}

$fieldId = [];
foreach (Craft::$app->getFields()->getAllFields() as $field) {
    $fieldId[$field->handle] = $field->id;
}

$volumes = Craft::$app->getVolumes();
$authorId = Craft::$app->getUsers()->getUserByUsernameOrEmail('admin')?->id
    ?? Craft::$app->getUsers()->getUserById(1)->id;

function save($element): void
{
    if (!Craft::$app->getElements()->saveElement($element)) {
        throw new RuntimeException(
            get_class($element) . ' "' . ($element->title ?? '') . '": ' . json_encode($element->getErrors(), JSON_UNESCAPED_UNICODE)
        );
    }
}

/**
 * A placeholder image: a flat neutral grey (#99a1af) at the given size,
 * written to a temp file and saved as an asset in $volumeHandle. The colour
 * and label arguments are accepted but ignored, so callers can keep hinting
 * at what the image would be.
 */
function placeholder(string $volumeHandle, string $filename, int $w, int $h, array $from = [], array $to = [], string $label = ''): Asset
{
    global $volumes;

    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 0x99, 0xa1, 0xaf));

    $tmp = Craft::$app->getPath()->getTempPath() . '/' . $filename;
    imagejpeg($im, $tmp, 88);

    $volume = $volumes->getVolumeByHandle($volumeHandle);
    $folder = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);

    $asset = new Asset();
    $asset->tempFilePath = $tmp;
    $asset->filename = $filename;
    $asset->newFolderId = $folder->id;
    $asset->setVolumeId($volume->id);
    $asset->avoidFilenameConflicts = true;
    $asset->setScenario(Asset::SCENARIO_CREATE);
    save($asset);

    return $asset;
}

function entry(string $section, string $type, string $title, array $fields = [], array $extra = []): Entry
{
    global $sectionId, $typeId, $authorId;

    $entry = new Entry();
    $entry->sectionId = $sectionId[$section];
    $entry->typeId = $typeId[$type];
    $entry->authorId = $authorId;
    $entry->title = $title;
    foreach ($extra as $k => $v) {
        if ($k === 'parentId') {
            $entry->setParentId($v);
        } else {
            $entry->$k = $v;
        }
    }
    $entry->setFieldValues($fields);
    save($entry);

    return $entry;
}

/** A Matrix block definition: [type, fields, title?]. */
function block(string $type, array $fields = [], ?string $title = null): array
{
    global $typeId;
    $out = ['type' => $type, 'fields' => $fields];
    if ($title !== null) {
        $out['title'] = $title;
    }
    return $out;
}

/** Turns block() definitions into the "newN" array a Matrix field accepts. */
function blocks(array $defs): array
{
    $out = [];
    foreach ($defs as $i => $def) {
        $out['new' . ($i + 1)] = $def;
    }
    return $out;
}

function cta(string $label, string $url, string $style = 'primary', string $icon = ''): array
{
    $type = 'url';
    if (str_starts_with($url, 'mailto:')) {
        $type = 'email';
        $url = substr($url, 7);
    }
    return block('cta', [
        'link' => ['value' => $url, 'type' => $type, 'label' => $label],
        'style' => $style,
        'iconName' => $icon,
    ]);
}

$lorem = '<p>Dit is voorbeeldtekst. Vervang deze door de tekst van de klant. Houd het kort en concreet: wat doet het bedrijf, voor wie, en wat levert het op?</p>';
$loremLong = $lorem . '<p>Een tweede alinea geeft ruimte voor nuance of een voorbeeld. Gebruik lijsten en tussenkoppen alleen als ze de lezer echt helpen.</p>';

// ---------------------------------------------------------------------------
// Images
// ---------------------------------------------------------------------------

echo "Images…\n";
$hero = placeholder('media', 'demo-hero.jpg', 2880, 1920, [40, 36, 120], [180, 90, 200], 'HERO 2880 x 1920');
$landscape = placeholder('media', 'demo-landscape.jpg', 2048, 1365, [230, 200, 150], [120, 160, 200], 'AFBEELDING');
$portraitA = placeholder('people', 'demo-persoon-1.jpg', 800, 800, [200, 160, 120], [120, 100, 160]);
$portraitB = placeholder('people', 'demo-persoon-2.jpg', 800, 800, [120, 180, 160], [60, 100, 140]);
$portraitC = placeholder('people', 'demo-persoon-3.jpg', 800, 800, [220, 140, 140], [140, 80, 120]);
$blogImages = [
    placeholder('media', 'demo-blog-1.jpg', 2048, 1152, [90, 120, 220], [200, 220, 240], 'BLOG 1'),
    placeholder('media', 'demo-blog-2.jpg', 2048, 1152, [220, 160, 90], [240, 220, 200], 'BLOG 2'),
    placeholder('media', 'demo-blog-3.jpg', 2048, 1152, [90, 200, 160], [200, 240, 220], 'BLOG 3'),
];
$logos = [];
foreach (['Noorderlicht', 'Bakkerij De Molen', 'Studio Vlak', 'Van Dijk & Zn.'] as $i => $name) {
    $logos[] = placeholder('logos', 'demo-logo-' . ($i + 1) . '.jpg', 480, 200, [235, 235, 235], [215, 215, 215], strtoupper($name));
}

// ---------------------------------------------------------------------------
// Globals
// ---------------------------------------------------------------------------

echo "Globals…\n";
$brand = GlobalSet::find()->handle('brand')->one();
$brand->setFieldValues([
    'footerText' => '<p>Een korte omschrijving van het bedrijf voor in de footer. Twee zinnen is genoeg.</p>',
]);
save($brand);

$contact = GlobalSet::find()->handle('contact')->one();
$contact->setFieldValues([
    'address' => ['new1' => [
        'countryCode' => 'NL',
        'addressLine1' => 'Voorbeeldstraat 12',
        'postalCode' => '1234 AB',
        'locality' => 'Utrecht',
        'fields' => ['googleMapsUrl' => 'https://maps.google.com/?q=Utrecht'],
    ]],
    'email' => 'info@voorbeeld.nl',
    'phone' => '+31 6 12 34 56 78',
    'enableWhatsapp' => true,
    'kvkNumber' => '12345678',
    'vatId' => 'NL123456789B01',
    'instagramUrl' => 'https://www.instagram.com/',
    'linkedinUrl' => 'https://www.linkedin.com/',
]);
save($contact);

$navigation = GlobalSet::find()->handle('navigation')->one();
$navigation->setFieldValues([
    'ctas' => blocks([cta('Neem contact op', '/contact', 'primary', 'arrow-right')]),
]);
save($navigation);

$settings = GlobalSet::find()->handle('settings')->one();
$settings->setFieldValues(['useParallax' => true]);
save($settings);

// ---------------------------------------------------------------------------
// Collections
// ---------------------------------------------------------------------------

echo "People, testimonials, companies, products…\n";
$people = [
    entry('people', 'person', 'Sanne de Vries', ['photo' => [$portraitA->id], 'position' => 'Oprichter', 'company' => 'Voorbeeld B.V.', 'location' => 'Utrecht']),
    entry('people', 'person', 'Daan Jansen', ['photo' => [$portraitB->id], 'position' => 'Strateeg', 'location' => 'Amsterdam']),
    entry('people', 'person', 'Fatima el Amrani', ['photo' => [$portraitC->id], 'position' => 'Ontwerper', 'location' => 'Rotterdam']),
];

$testimonials = [
    entry('testimonials', 'testimonial', 'Lotte Bakker', ['quote' => '<p>Binnen twee weken stond er een site die precies aanvoelt als ons bedrijf. De samenwerking was prettig en snel.</p>', 'authorPosition' => 'Eigenaar', 'authorCompany' => 'Bakkerij De Molen', 'authorPhoto' => [$portraitB->id]]),
    entry('testimonials', 'testimonial', 'Mark Visser', ['quote' => '<p>Eindelijk een website die we zelf kunnen bijhouden zonder handleiding. Elke sectie spreekt voor zich.</p>', 'authorPosition' => 'Marketing', 'authorCompany' => 'Noorderlicht']),
    entry('testimonials', 'testimonial', 'Eva Smit', ['quote' => '<p>Snel, helder en met oog voor detail. We krijgen regelmatig complimenten over de site.</p>', 'authorCompany' => 'Studio Vlak', 'authorPhoto' => [$portraitC->id]]),
];

foreach ($logos as $i => $logo) {
    entry('companies', 'company', ['Noorderlicht', 'Bakkerij De Molen', 'Studio Vlak', 'Van Dijk & Zn.'][$i], ['logo' => [$logo->id], 'websiteUrl' => 'https://example.com']);
}

$products = [
    entry('products', 'product', 'Starter', ['price' => '49', 'priceUnit' => 'maand', 'description' => 'Voor wie klein wil beginnen.', 'features' => [['feature' => 'Eén pagina'], ['feature' => 'Onderhoud inbegrepen'], ['feature' => 'E-mailsupport']], 'ctas' => blocks([cta('Kies Starter', '/contact', 'secondary')])]),
    entry('products', 'product', 'Groei', ['price' => '99', 'fromPrice' => true, 'priceUnit' => 'maand', 'description' => 'Het pakket dat de meeste klanten kiezen.', 'features' => [['feature' => 'Tot tien pagina\'s'], ['feature' => 'Blog'], ['feature' => 'Maandelijkse update'], ['feature' => 'Telefonische support']], 'ctas' => blocks([cta('Kies Groei', '/contact', 'primary')])]),
    entry('products', 'product', 'Maatwerk', ['price' => '1.950', 'fromPrice' => true, 'description' => 'Alles wat je nodig hebt, op maat gemaakt.', 'features' => [['feature' => 'Onbeperkt pagina\'s'], ['feature' => 'Eigen ontwerp'], ['feature' => 'Koppelingen'], ['feature' => 'Prioriteitssupport']], 'ctas' => blocks([cta('Plan een gesprek', '/contact', 'secondary')])]),
];

// ---------------------------------------------------------------------------
// Blog
// ---------------------------------------------------------------------------

echo "Blog…\n";
$blogTitles = [
    'Waarom een simpele website vaak beter werkt',
    'Vijf vragen om te stellen voordat je een website laat maken',
    'Zo houd je je website zelf up-to-date',
];
foreach ($blogTitles as $i => $title) {
    entry('blogs', 'blog', $title, [
        'summary' => 'Een korte samenvatting van het artikel, die in het overzicht en bij het delen op social media wordt getoond.',
        'image' => [$blogImages[$i]->id],
        'body' => blocks([
            block('bodyText', ['text' => '<p><strong>Dit is de inleiding.</strong> ' . strip_tags($lorem) . '</p><h2>Een tussenkop</h2>' . $loremLong . '<ul><li>Een puntsgewijze lijst</li><li>Met een paar punten</li></ul>']),
            block('bodyImages', ['images' => [$landscape->id]]),
            block('bodyText', ['text' => '<h2>Nog een tussenkop</h2>' . $lorem . '<blockquote><p>Een citaat dat de kern van het artikel samenvat.</p></blockquote>' . $lorem]),
            block('bodyItems', ['items' => blocks([
                block('item', ['image' => [$landscape->id], 'text' => $lorem], 'Eerste item'),
                block('item', ['image' => [$blogImages[($i + 1) % 3]->id], 'text' => $lorem], 'Tweede item'),
            ])]),
            block('bodyVideo', ['youtubeUrl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']),
        ]),
    ], ['postDate' => new DateTime("-{$i} weeks")]);
}

// ---------------------------------------------------------------------------
// Pages
// ---------------------------------------------------------------------------

echo "Pages…\n";

$header = fn(string $eyebrow, string $intro = '') => ['eyebrow' => $eyebrow, 'intro' => $intro ? "<p>$intro</p>" : ''];

entry('pages', 'page', 'Home', [
    'seoDescription' => 'Voorbeeldsite gebouwd op de boilerplate. Vervang deze omschrijving.',
    'sections' => blocks([
        block('hero', [
            'eyebrow' => 'Welkom',
            'text' => '<p>Een pakkende openingszin die in één keer duidelijk maakt wat je doet en voor wie. Daaronder één knop, hooguit twee.</p>',
            'image' => [$hero->id],
            'height' => 'medium',
            'textWidth' => '75',
            'theme' => 'contrast',
            'ctas' => blocks([cta('Bekijk ons werk', '#werk', 'primary', 'arrow-right'), cta('Meer over ons', '/over-ons', 'secondary')]),
        ], 'Duidelijke websites voor ondernemers die vooruit willen'),
        block('text', $header('Over ons', 'Een korte introductie van twee of drie zinnen die de lezer nieuwsgierig maakt.') + [
            'text' => $loremLong,
            'sectionHeaderAlignment' => 'left',
            'ctas' => blocks([cta('Lees ons verhaal', '/over-ons', 'text')]),
        ], 'Wij maken websites die voor je werken'),
        block('columns', $header('Wat we doen') + [
            'theme' => 'tint',
            'sectionHeaderAlignment' => 'top',
            'columns' => blocks([
                block('column', ['iconName' => 'pencil-ruler', 'text' => $lorem], 'Ontwerp'),
                block('column', ['iconName' => 'code', 'text' => $lorem], 'Ontwikkeling'),
                block('column', ['iconName' => 'rocket', 'text' => $lorem, 'ctas' => blocks([cta('Meer info', '/contact', 'text')])], 'Lancering'),
            ]),
        ], 'Drie stappen naar een nieuwe website'),
        block('cards', $header('Ons werk', 'Een greep uit recente projecten.') + [
            'sectionHeaderAlignment' => 'left',
            'theme' => 'contrast',
            'applyThemeTo' => 'cards',
            'cards' => blocks([
                block('card', ['image' => [$landscape->id], 'eyebrow' => 'Webshop', 'text' => $lorem, 'ctas' => blocks([cta('Bekijk project', '/over-ons', 'text')])], 'Bakkerij De Molen'),
                block('card', ['image' => [$blogImages[0]->id], 'eyebrow' => 'Website', 'text' => $lorem, 'ctas' => blocks([cta('Bekijk project', '/over-ons', 'text')])], 'Noorderlicht'),
            ]),
        ], 'Projecten waar we trots op zijn'),
        block('stats', $header('In cijfers') + [
            'stats' => [['value' => '120+', 'label' => 'websites opgeleverd'], ['value' => '9,4', 'label' => 'gemiddelde beoordeling'], ['value' => '12', 'label' => 'jaar ervaring']],
            'sectionHeaderAlignment' => 'left',
        ], 'Resultaten die tellen'),
        block('textMedia', $header('Aanpak') + [
            'text' => $loremLong,
            'image' => [$landscape->id],
            'textPosition' => 'left',
            'theme' => 'tint',
            'ctas' => blocks([cta('Onze aanpak', '/over-ons', 'secondary')]),
        ], 'Persoonlijk, snel en zonder gedoe'),
        block('testimonials', $header('Klanten aan het woord') + ['sectionHeaderAlignment' => 'top', 'theme' => 'tint'], 'Wat klanten zeggen'),
        block('companies', $header('Vertrouwd door'), ''),
        block('products', $header('Pakketten', 'Kies het pakket dat bij je past. Twijfel je? We denken graag mee.') + ['sectionHeaderAlignment' => 'top', 'theme' => 'tint'], 'Heldere prijzen'),
        block('accordion', $header('Veelgestelde vragen') + [
            'accordionItems' => blocks([
                block('accordionItem', ['text' => $lorem], 'Hoe lang duurt het maken van een website?'),
                block('accordionItem', ['text' => $lorem], 'Kan ik de website zelf aanpassen?'),
                block('accordionItem', ['text' => $lorem], 'Wat kost het onderhoud?'),
            ]),
            'ctas' => blocks([cta('Stel je vraag', '/contact', 'text')]),
        ], 'Antwoord op je vragen'),
        block('people', $header('Team') + ['sectionHeaderAlignment' => 'top'], 'De mensen achter het werk'),
        block('ticker', $header('Impressie') + ['rows' => '2', 'tickerItems' => blocks([
            block('image', ['image' => [$landscape->id]]),
            block('testimonial', ['quote' => '<p>Kort en krachtig citaat voor in de ticker.</p>', 'authorCompany' => 'Studio Vlak'], 'Eva Smit'),
            block('image', ['image' => [$blogImages[1]->id]]),
            block('image', ['image' => [$blogImages[2]->id]]),
            block('testimonial', ['quote' => '<p>Nog een citaat, net zo kort.</p>', 'authorCompany' => 'Noorderlicht'], 'Mark Visser'),
            block('image', ['image' => [$blogImages[0]->id]]),
        ])], 'Sfeerimpressie'),
        block('blogIndex', $header('Blog', 'De nieuwste artikelen.') + ['limit' => 3, 'sectionHeaderAlignment' => 'left'], 'Lees verder'),
        block('embed', $header('Nieuwsbrief') + [
            'embedCode' => '<form onsubmit="return false"><label for="demo-email">E-mailadres</label><input id="demo-email" type="email" placeholder="jij@voorbeeld.nl"><p style="margin-top:1rem"><button type="submit">Aanmelden</button></p></form>',
            'sectionHeaderAlignment' => 'left',
        ], 'Blijf op de hoogte'),
        block('callToAction', [
            'text' => '<p>Plan een vrijblijvend gesprek en ontdek wat we voor je kunnen doen.</p>',
            'theme' => 'contrast',
            'ctas' => blocks([cta('Neem contact op', '/contact', 'primary', 'arrow-right')]),
        ], 'Klaar om te beginnen?'),
    ]),
], ['slug' => 'home']);

$about = entry('pages', 'page', 'Over ons', [
    'sections' => blocks([
        block('hero', ['text' => '<p>Een pagina zonder achtergrondafbeelding: de hero volgt dan het gekozen kleurthema.</p>', 'theme' => 'tint', 'height' => 'fit'], 'Over ons'),
        block('text', $header('Verhaal') + ['text' => $loremLong, 'sectionHeaderAlignment' => 'top'], 'Hoe het begon'),
        block('people', $header('Team') + ['sectionHeaderAlignment' => 'left', 'theme' => 'tint'], 'Het team'),
    ]),
], ['slug' => 'over-ons']);

entry('pages', 'page', 'Werkwijze', [
    'sections' => blocks([
        block('hero', ['text' => '<p>Een subpagina onder "Over ons".</p>', 'theme' => 'contrast'], 'Werkwijze'),
        block('text', ['text' => $loremLong, 'sectionHeaderAlignment' => 'top'], 'Stap voor stap'),
    ]),
], ['slug' => 'werkwijze', 'parentId' => $about->id]);

entry('pages', 'page', 'Blog', [
    'sections' => blocks([
        block('hero', ['text' => '<p>Artikelen over websites, ondernemen en alles daartussenin.</p>', 'theme' => 'tint'], 'Blog'),
        block('blogIndex', ['sectionHeaderAlignment' => 'top'], ''),
    ]),
], ['slug' => 'blog']);

entry('pages', 'page', 'Contact', [
    'sections' => blocks([
        block('hero', ['text' => '<p>Mail, bel of app — we reageren binnen een werkdag.</p>', 'theme' => 'contrast', 'ctas' => blocks([cta('info@voorbeeld.nl', 'mailto:info@voorbeeld.nl', 'primary', 'mail')])], 'Contact'),
        block('embed', $header('Formulier') + [
            'embedCode' => '<form onsubmit="return false"><p><label for="demo-name">Naam</label><input id="demo-name" type="text"></p><p style="margin-top:1rem"><label for="demo-message">Bericht</label><textarea id="demo-message"></textarea></p><p style="margin-top:1rem"><button type="submit">Versturen</button></p></form>',
        ], 'Stuur een bericht'),
    ]),
], ['slug' => 'contact']);

entry('legal', 'page', 'Privacyverklaring', [
    'sections' => blocks([
        block('hero', ['theme' => 'tint'], 'Privacyverklaring'),
        block('text', ['text' => $loremLong . $loremLong, 'sectionHeaderAlignment' => 'top'], ''),
    ]),
], ['slug' => 'privacy']);

entry('legal', 'page', 'Algemene voorwaarden', [
    'sections' => blocks([
        block('hero', ['theme' => 'tint'], 'Algemene voorwaarden'),
        block('text', ['text' => $loremLong . $loremLong, 'sectionHeaderAlignment' => 'top'], ''),
    ]),
], ['slug' => 'voorwaarden']);

echo "Done. Open the site to see every section in use.\n";
