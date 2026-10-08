<?php
/**
 * Generates config/project/** from the declarative content model below.
 *
 * This is a BOOTSTRAP tool for the boilerplate itself, not for client sites.
 * Once the config has been applied, config/project is the source of truth and
 * you edit the model in the control panel. Re-running this script overwrites
 * every generated file — only do that on a fresh install, or when you are
 * deliberately re-baselining the boilerplate.
 *
 *   ddev exec php scripts/build-project-config.php
 *   ddev craft project-config/apply --force
 *   ddev craft project-config/rebuild
 *
 * Handles are English (they are what templates read); labels and instructions
 * are Dutch (they are what clients read).
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$root = dirname(__DIR__) . '/config/project';
$now = '2026-09-12T12:00:00+00:00';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * The installer creates the primary site and its group with random uids, and
 * Craft refuses to delete a primary site. Reuse whatever is already on disk so
 * the generated config is an update rather than a swap.
 */
$pinned = [];
foreach (glob("$root/sites/default--*.yaml") as $f) {
    $pinned['site:default'] = substr(basename($f, '.yaml'), strlen('default--'));
}
foreach (glob("$root/siteGroups/*.yaml") as $f) {
    $pinned['siteGroup:default'] = basename($f, '.yaml');
}

/** Deterministic v4-shaped uid, so re-running the generator is stable. */
function u(string $key): string
{
    global $pinned;
    if (isset($pinned[$key])) {
        return $pinned[$key];
    }

    $h = md5('boilerplate:' . $key);
    return sprintf(
        '%s-%s-4%s-%s%s-%s',
        substr($h, 0, 8),
        substr($h, 8, 4),
        substr($h, 13, 3),
        dechex(8 + (hexdec($h[16]) % 4)),
        substr($h, 17, 3),
        substr($h, 20, 12),
    );
}

function put(string $path, array $data): void
{
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, Yaml::dump($data, 20, 2, Yaml::DUMP_NULL_AS_TILDE));
}

/** Craft encodes ordered maps inside settings as __assoc__ pair lists. */
function assoc(array $pairs): array
{
    $out = [];
    foreach ($pairs as $k => $v) {
        $out[] = [$k, $v];
    }
    return ['__assoc__' => $out];
}

/** A field layout element referencing a custom field. */
function fld(string $layoutKey, string $fieldHandle, array $o = []): array
{
    global $now;
    return [
        'dateAdded' => $now,
        'editCondition' => null,
        'elementCondition' => $o['condition'] ?? null,
        'fieldUid' => u('field:' . $fieldHandle),
        'handle' => $o['handle'] ?? null,
        'instructions' => $o['instructions'] ?? null,
        'label' => $o['label'] ?? null,
        'required' => $o['required'] ?? false,
        'tip' => $o['tip'] ?? null,
        'type' => 'craft\\fieldlayoutelements\\CustomField',
        'uid' => layoutElUid($layoutKey, $o['handle'] ?? $fieldHandle),
        'userCondition' => null,
        'warning' => null,
        'width' => $o['width'] ?? 100,
    ];
}

function layoutElUid(string $layoutKey, string $handle): string
{
    return u('layoutel:' . $layoutKey . ':' . $handle);
}

/** The native entry title field layout element. */
function titleEl(string $layoutKey, array $o = []): array
{
    global $now;
    return [
        'autocapitalize' => true,
        'autocomplete' => false,
        'autocorrect' => true,
        'class' => null,
        'dateAdded' => $now,
        'disabled' => false,
        'elementCondition' => null,
        'id' => null,
        'inputType' => null,
        'instructions' => $o['instructions'] ?? null,
        'label' => $o['label'] ?? null,
        'max' => null,
        'min' => null,
        'name' => null,
        'orientation' => null,
        'placeholder' => null,
        'readonly' => false,
        'required' => $o['required'] ?? true,
        'size' => null,
        'step' => null,
        'tip' => null,
        'title' => null,
        'type' => 'craft\\fieldlayoutelements\\entries\\EntryTitleField',
        'uid' => u('layoutel:' . $layoutKey . ':title'),
        'userCondition' => null,
        'warning' => null,
        'width' => 100,
    ];
}

function headingEl(string $layoutKey, string $heading): array
{
    global $now;
    return [
        'dateAdded' => $now,
        'elementCondition' => null,
        'heading' => $heading,
        'type' => 'craft\\fieldlayoutelements\\Heading',
        'uid' => u('layoutel:' . $layoutKey . ':heading:' . $heading),
        'userCondition' => null,
    ];
}

/** A native (non-custom-field) layout element with the text-input shape. */
function nativeTextEl(string $layoutKey, string $type, string $key, array $extra = []): array
{
    global $now;
    return array_merge([
        'autocapitalize' => true,
        'autocomplete' => false,
        'autocorrect' => true,
        'class' => null,
        'dateAdded' => $now,
        'disabled' => false,
        'elementCondition' => null,
        'id' => null,
        'inputType' => null,
        'instructions' => null,
        'label' => null,
        'max' => null,
        'min' => null,
        'name' => null,
        'orientation' => null,
        'placeholder' => null,
        'readonly' => false,
        'requirable' => false,
        'size' => null,
        'step' => null,
        'tip' => null,
        'title' => null,
        'type' => $type,
        'uid' => u('layoutel:' . $layoutKey . ':' . $key),
        'userCondition' => null,
        'warning' => null,
        'width' => 100,
    ], $extra);
}

/**
 * Builds the tabs of a field layout. $tabs is [tabName => [element, ...]] where
 * an element is 'title', ['heading', 'Text'], or [fieldHandle, options].
 */
function tabs(string $layoutKey, array $tabs, array $titleOpts = []): array
{
    $out = [];
    foreach ($tabs as $name => $elements) {
        $els = [];
        foreach ($elements as $el) {
            if ($el === 'title') {
                $els[] = titleEl($layoutKey, $titleOpts);
            } elseif ($el[0] === 'heading') {
                $els[] = headingEl($layoutKey, $el[1]);
            } else {
                $els[] = fld($layoutKey, $el[0], $el[1] ?? []);
            }
        }
        $out[] = [
            'elementCondition' => null,
            'elements' => $els,
            'name' => $name,
            'uid' => u('tab:' . $layoutKey . ':' . $name),
            'userCondition' => null,
        ];
    }
    return $out;
}

/** "Show this field only when <other field> is empty." */
function emptyCondition(string $layoutKey, string $otherHandle, string $ruleClass): array
{
    $rule = [
        'class' => $ruleClass,
        'fieldUid' => u('field:' . $otherHandle),
        'layoutElementUid' => layoutElUid($layoutKey, $otherHandle),
        'operator' => 'empty',
        'uid' => u('rule:' . $layoutKey . ':' . $otherHandle),
    ];
    if ($ruleClass === 'craft\\fields\\conditions\\TextFieldConditionRule') {
        $rule['value'] = '';
    }
    return [
        'class' => 'craft\\elements\\conditions\\entries\\EntryCondition',
        'conditionRules' => [$rule],
        'elementType' => 'craft\\elements\\Entry',
        'fieldContext' => 'global',
    ];
}

// ---------------------------------------------------------------------------
// Site
// ---------------------------------------------------------------------------

$siteGroupUid = u('siteGroup:default');
put("$root/siteGroups/$siteGroupUid.yaml", ['name' => 'Boilerplate']);

$siteUid = u('site:default');
put("$root/sites/default--$siteUid.yaml", [
    'baseUrl' => '$PRIMARY_SITE_URL',
    'enabled' => true,
    'handle' => 'default',
    'hasUrls' => true,
    'language' => 'nl',
    'name' => 'Boilerplate',
    'primary' => true,
    'siteGroup' => $siteGroupUid,
    'sortOrder' => 1,
]);

// ---------------------------------------------------------------------------
// Volumes
// ---------------------------------------------------------------------------

$volumes = [
    // handle, name, subpath, sortOrder
    ['media', 'Media', 'media', 1],
    ['logos', "Logo's", 'logos', 2],
    ['people', 'Personen', 'personen', 3],
];

foreach ($volumes as [$handle, $name, $subpath, $sort]) {
    $uid = u('volume:' . $handle);
    put("$root/volumes/$handle--$uid.yaml", [
        'altTranslationKeyFormat' => null,
        'altTranslationMethod' => 'none',
        'fieldLayouts' => [
            u('volumeLayout:' . $handle) => [
                'cardThumbAlignment' => 'end',
                'tabs' => [[
                    'elementCondition' => null,
                    'elements' => [
                        nativeTextEl('volume:' . $handle, 'craft\\fieldlayoutelements\\assets\\AssetTitleField', 'title'),
                        [
                            'class' => null,
                            'dateAdded' => $now,
                            'disabled' => false,
                            'elementCondition' => null,
                            'id' => null,
                            'instructions' => 'Beschrijf kort wat er op de afbeelding te zien is. Belangrijk voor toegankelijkheid en zoekmachines.',
                            'label' => null,
                            'name' => null,
                            'orientation' => null,
                            'placeholder' => null,
                            'readonly' => false,
                            'requirable' => true,
                            'required' => false,
                            'rows' => null,
                            'tip' => null,
                            'title' => null,
                            'type' => 'craft\\fieldlayoutelements\\assets\\AltField',
                            'uid' => u('layoutel:volume:' . $handle . ':alt'),
                            'userCondition' => null,
                            'warning' => null,
                            'width' => 100,
                        ],
                    ],
                    'name' => 'Inhoud',
                    'uid' => u('volumeTab:' . $handle),
                    'userCondition' => null,
                ]],
            ],
        ],
        'fs' => 'local',
        'handle' => $handle,
        'name' => $name,
        'sortOrder' => $sort,
        'subpath' => $subpath,
        'titleTranslationKeyFormat' => null,
        'titleTranslationMethod' => 'site',
        'transformFs' => '',
        'transformSubpath' => '',
    ]);
}

$vol = fn(string $handle) => 'volume:' . u('volume:' . $handle);

// ---------------------------------------------------------------------------
// Fields
// ---------------------------------------------------------------------------

$fields = [];

function base(string $type, string $handle, string $name, array $settings, array $o = []): array
{
    return [
        'columnSuffix' => null,
        'handle' => $handle,
        'instructions' => $o['instructions'] ?? null,
        'name' => $name,
        'searchable' => $o['searchable'] ?? false,
        'settings' => $settings,
        'translationKeyFormat' => null,
        'translationMethod' => $o['translate'] ?? 'none',
        'type' => $type,
    ];
}

function plainText(string $handle, string $name, array $o = []): array
{
    return base('craft\\fields\\PlainText', $handle, $name, [
        'byteLimit' => null,
        'charLimit' => $o['charLimit'] ?? null,
        'code' => $o['code'] ?? false,
        'initialRows' => $o['rows'] ?? 4,
        'multiline' => $o['multiline'] ?? false,
        'placeholder' => $o['placeholder'] ?? null,
        'uiMode' => 'normal',
    ], $o);
}

/**
 * The paragraph-size presets shared by both rich text fields. Editors pick a
 * size from the heading dropdown; the front end styles the resulting classes
 * in 4-components/rich-text.scss.
 */
function ckeParagraphSizes(): string
{
    return <<<'JS'
return {
  heading: {
    options: [
      { model: 'paragraph', title: 'Alinea', class: 'ck-heading_paragraph' },
      { model: 'paragraphSmall', view: { name: 'p', classes: ['size-s'] }, title: 'Alinea (klein)', class: 'ck-heading_paragraph', converterPriority: 'high' },
      { model: 'paragraphLarge', view: { name: 'p', classes: ['size-l'] }, title: 'Alinea (groot)', class: 'ck-heading_paragraph', converterPriority: 'high' },
      { model: 'paragraphXl', view: { name: 'p', classes: ['size-xl'] }, title: 'Alinea (extra groot)', class: 'ck-heading_paragraph', converterPriority: 'high' },
      __HEADINGS__
    ],
  },
};
JS;
}

function ckeditor(string $handle, string $name, array $o = []): array
{
    $article = $o['article'] ?? false;

    $headings = $article
        ? "{ model: 'heading2', view: 'h2', title: 'Kop 2', class: 'ck-heading_heading2' },\n      { model: 'heading3', view: 'h3', title: 'Kop 3', class: 'ck-heading_heading3' },"
        : '';

    $toolbar = $article
        ? ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', 'blockQuote', '|', 'sourceEditing']
        : ['heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', '|', 'sourceEditing'];

    return base('craft\\ckeditor\\Field', $handle, $name, [
        'advancedLinkFields' => ['target'],
        'availableTransforms' => '',
        'availableVolumes' => '',
        'characterLimit' => null,
        'css' => ".ck.ck-content { font-size: 16px; line-height: 1.5; }\n.ck.ck-content p.size-s { font-size: .875em; }\n.ck.ck-content p.size-l { font-size: 1.125em; }\n.ck.ck-content p.size-xl { font-size: 1.25em; }",
        'cssFile' => null,
        'defaultTransform' => null,
        'defaultUploadLocationSubpath' => null,
        'defaultUploadLocationVolume' => null,
        'fullGraphqlData' => true,
        'headingLevels' => $article ? ['2', '3'] : false,
        'imageEntryTypeUid' => null,
        'imageFieldUid' => null,
        'imageMode' => 'img',
        'js' => str_replace('__HEADINGS__', $headings, ckeParagraphSizes()),
        'jsFile' => null,
        'options' => null,
        'parseEmbeds' => false,
        'purifierConfig' => null,
        'purifyHtml' => true,
        'showUnpermittedFiles' => false,
        'showUnpermittedVolumes' => false,
        'showWordCount' => false,
        'sourceEditingGroups' => ['__ADMINS__'],
        'toolbar' => $toolbar,
        'wordLimit' => null,
    ], ['translate' => 'site'] + $o);
}

function assets(string $handle, string $name, string $volume, array $o = []): array
{
    return base('craft\\fields\\Assets', $handle, $name, [
        'allowSelfRelations' => false,
        'allowSubfolders' => false,
        'allowUploads' => true,
        'allowedKinds' => $o['kinds'] ?? ['image'],
        'branchLimit' => null,
        'defaultPlacement' => 'end',
        'defaultUploadLocationSource' => $volume,
        'defaultUploadLocationSubpath' => null,
        'maintainHierarchy' => false,
        'maxRelations' => $o['max'] ?? 1,
        'minRelations' => null,
        'previewMode' => 'full',
        'restrictFiles' => true,
        'restrictLocation' => true,
        'restrictedDefaultUploadSubpath' => null,
        'restrictedLocationSource' => $volume,
        'restrictedLocationSubpath' => $o['subpath'] ?? null,
        'selectionLabel' => $o['selectionLabel'] ?? 'Afbeelding kiezen',
        'showCardsInGrid' => false,
        'showSearchInput' => true,
        'showSiteMenu' => false,
        'showUnpermittedFiles' => false,
        'showUnpermittedVolumes' => false,
        'sources' => '*',
        'targetSiteId' => null,
        'validateRelatedElements' => false,
        'viewMode' => $o['viewMode'] ?? 'large',
    ], $o);
}

function entries(string $handle, string $name, array $sectionHandles, array $o = []): array
{
    return base('craft\\fields\\Entries', $handle, $name, [
        'allowSelfRelations' => false,
        'branchLimit' => null,
        'defaultPlacement' => 'end',
        'maintainHierarchy' => false,
        'maxRelations' => $o['max'] ?? null,
        'minRelations' => null,
        'selectionLabel' => $o['selectionLabel'] ?? 'Kiezen',
        'showCardsInGrid' => false,
        'showSearchInput' => true,
        'showSiteMenu' => false,
        'showUnpermittedEntries' => false,
        'showUnpermittedSections' => false,
        'sources' => array_map(fn($h) => 'section:' . u('section:' . $h), $sectionHandles),
        'targetSiteId' => null,
        'validateRelatedElements' => false,
        'viewMode' => $o['viewMode'] ?? 'cards',
    ], $o);
}

function lightswitch(string $handle, string $name, bool $default = false, array $o = []): array
{
    return base('craft\\fields\\Lightswitch', $handle, $name, [
        'default' => $default,
        'offLabel' => null,
        'onLabel' => null,
    ], $o);
}

/** @param array<int, array{0:string,1:string,2?:string}> $options [label, value, icon?] */
function options(array $options, ?string $default = null): array
{
    $out = [];
    foreach ($options as $i => $opt) {
        $out[] = assoc([
            'label' => $opt[0],
            'value' => $opt[1],
            'icon' => $opt[2] ?? '',
            'default' => ($default === null ? $i === 0 : $opt[1] === $default) ? '1' : '',
        ]);
    }
    return $out;
}

function buttonGroup(string $handle, string $name, array $options, array $o = []): array
{
    return base('craft\\fields\\ButtonGroup', $handle, $name, [
        'customOptions' => false,
        'iconsOnly' => false,
        'options' => options($options),
    ], $o);
}

/** @param array<int, array{0:string,1:string,2:string}> $options [label, value, color] */
function dropdownColors(string $handle, string $name, array $options, array $o = []): array
{
    $out = [];
    foreach ($options as $i => [$label, $value, $color]) {
        $out[] = assoc(['label' => $label, 'value' => $value, 'icon' => '', 'color' => $color, 'default' => $i === 0 ? '1' : '']);
    }
    return base('craft\\fields\\Dropdown', $handle, $name, [
        'customOptions' => false,
        'options' => $out,
    ], $o);
}

function table(string $handle, string $name, array $columns, array $o = []): array
{
    $cols = [];
    foreach ($columns as $i => $c) {
        $cols['col' . ($i + 1)] = assoc([
            'heading' => $c['heading'],
            'handle' => $c['handle'],
            'width' => $c['width'] ?? '',
            'type' => $c['type'],
        ] + (isset($c['options']) ? ['options' => $c['options']] : []));
    }
    return base('craft\\fields\\Table', $handle, $name, [
        'addRowLabel' => $o['addRowLabel'] ?? 'Rij toevoegen',
        'columns' => assoc($cols),
        'defaults' => null,
        'maxRows' => $o['maxRows'] ?? null,
        'minRows' => null,
        'staticRows' => false,
    ], ['translate' => 'site'] + $o);
}

function matrix(string $handle, string $name, array $entryTypeKeys, array $o = []): array
{
    $ets = [];
    foreach ($entryTypeKeys as $k) {
        $ets[] = assoc(['uid' => u('et:' . $k), 'group' => 'Algemeen']);
    }
    return base('craft\\fields\\Matrix', $handle, $name, [
        'createButtonLabel' => $o['createButtonLabel'] ?? null,
        'defaultIndexViewMode' => 'cards',
        'enableVersioning' => false,
        'entryTypes' => $ets,
        'includeTableView' => false,
        'maxEntries' => $o['max'] ?? null,
        'minEntries' => null,
        'pageSize' => 50,
        'propagationKeyFormat' => null,
        'propagationMethod' => 'all',
        'showCardsInGrid' => false,
        'viewMode' => $o['viewMode'] ?? 'blocks',
    ], ['translate' => 'site'] + $o);
}

function linkField(string $handle, string $name, array $types, array $o = []): array
{
    $typeSettings = [];
    if (in_array('entry', $types)) {
        $typeSettings['entry'] = assoc([
            'sources' => '*',
            'showUnpermittedSections' => '',
            'showUnpermittedEntries' => '',
        ]);
    }
    if (in_array('url', $types)) {
        $typeSettings['url'] = assoc([
            'allowRootRelativeUrls' => '1',
            'allowAnchors' => '1',
            'allowCustomSchemes' => '',
        ]);
    }
    return base('craft\\fields\\Link', $handle, $name, [
        'advancedFields' => $o['advancedFields'] ?? [],
        'fullGraphqlData' => true,
        'maxLength' => 255,
        'showLabelField' => $o['showLabelField'] ?? false,
        'typeSettings' => assoc($typeSettings),
        'types' => $types,
    ], $o);
}

// --- Tekst ---------------------------------------------------------------

$fields['eyebrow'] = plainText('eyebrow', 'Bovenkop', [
    'instructions' => 'Korte tekst die boven de titel staat, bijvoorbeeld een thema of categorie.',
    'translate' => 'site',
]);
$fields['line'] = plainText('line', 'Tekstregel', [
    'instructions' => 'Eén regel tekst. Wordt op verschillende plekken hergebruikt met een eigen label.',
    'translate' => 'site',
]);
$fields['textarea'] = plainText('textarea', 'Tekst (zonder opmaak)', [
    'multiline' => true,
    'rows' => 3,
    'translate' => 'site',
]);
$fields['richText'] = ckeditor('richText', 'Tekst (opgemaakt)', [
    'instructions' => 'Kies via het menu "Alinea" een tekstgrootte.',
]);
$fields['articleText'] = ckeditor('articleText', 'Artikeltekst', [
    'article' => true,
    'instructions' => 'Tekst met tussenkoppen, lijsten en citaten voor langere artikelen.',
]);
$fields['code'] = plainText('code', 'Code', [
    'code' => true,
    'multiline' => true,
    'rows' => 6,
]);

// --- Opties (niet vertaalbaar) --------------------------------------------

$fields['theme'] = dropdownColors('theme', 'Kleurthema', [
    ['Geen', 'none', '#ffffff'],
    ['Tint', 'tint', '#f3f4f6'],
    ['Contrast', 'contrast', '#101828'],
], [
    'instructions' => 'De achtergrond van de sectie: geen (wit), een lichte tint, of contrast (donker met lichte tekst). De exacte kleuren worden per website ingesteld in de huisstijl.',
]);
$fields['isFullWidth'] = lightswitch('isFullWidth', 'Volledige breedte?', false, [
    'instructions' => 'Laat de achtergrondkleur over de volledige schermbreedte lopen in plaats van binnen een afgerond vlak.',
]);
$fields['applyThemeTo'] = buttonGroup('applyThemeTo', 'Kleurthema toepassen op', [
    ['Sectie', 'section'],
    ['Kaarten', 'cards'],
]);
$fields['sectionHeaderAlignment'] = buttonGroup('sectionHeaderAlignment', 'Positie van de kop', [
    ['Links', 'left', 'align-left'],
    ['Boven', 'top', 'align-center'],
], [
    'instructions' => '"Links" plaatst de kop naast de inhoud, "Boven" plaatst de kop gecentreerd boven de inhoud.',
]);
$fields['textPosition'] = buttonGroup('textPosition', 'Positie van de tekst', [
    ['Links', 'left', 'arrow-left'],
    ['Rechts', 'right', 'arrow-right'],
]);
$fields['textWidth'] = buttonGroup('textWidth', 'Breedte van de tekst', [
    ['75%', '75'],
    ['50%', '50'],
]);
$fields['height'] = buttonGroup('height', 'Hoogte', [
    ['Passend', 'fit'],
    ['Klein', 'small'],
    ['Middel', 'medium'],
    ['Groot', 'large'],
], [
    'instructions' => '"Passend" volgt de inhoud; de andere opties vullen een deel van het scherm.',
]);
$fields['rows'] = buttonGroup('rows', 'Aantal rijen', [
    ['Eén', '1'],
    ['Twee', '2'],
]);
$fields['style'] = buttonGroup('style', 'Stijl', [
    ['Primaire knop', 'primary'],
    ['Secundaire knop', 'secondary'],
    ['Tekstlink', 'text'],
]);
$fields['includeInNav'] = lightswitch('includeInNav', 'Tonen in navigatie?', true, [
    'instructions' => 'Zet uit om de pagina wel te publiceren maar niet in het menu en de footer te tonen.',
]);
$fields['useParallax'] = lightswitch('useParallax', 'Parallax-effect op afbeeldingen?', false, [
    'instructions' => 'Laat afbeeldingen subtiel meebewegen tijdens het scrollen.',
]);
$fields['enableWhatsapp'] = lightswitch('enableWhatsapp', 'WhatsApp-link tonen?', false, [
    'instructions' => 'Voegt een WhatsApp-knop toe die het telefoonnummer hierboven gebruikt.',
]);
$fields['fromPrice'] = lightswitch('fromPrice', 'Vanafprijs?', false, [
    'instructions' => 'Toont "v.a." voor de prijs.',
]);
$fields['iconName'] = plainText('iconName', 'Icoon', [
    'instructions' => 'De naam van een icoon uit de Lucide-set, bijvoorbeeld "sparkles" of "calendar-check". Zie https://lucide.dev/icons/',
    'placeholder' => 'sparkles',
]);
$fields['limit'] = base('craft\\fields\\Number', 'limit', 'Maximum aantal', [
    'decimals' => 0,
    'defaultValue' => null,
    'max' => null,
    'min' => 1,
    'prefix' => null,
    'previewCurrency' => null,
    'previewFormat' => 'none',
    'size' => null,
    'step' => null,
    'suffix' => null,
], [
    'instructions' => 'Laat leeg om geen maximum in te stellen.',
]);

// --- Media ---------------------------------------------------------------

$fields['image'] = assets('image', 'Afbeelding', $vol('media'));
$fields['images'] = assets('images', 'Afbeeldingen', $vol('media'), [
    'max' => 2,
    'selectionLabel' => 'Afbeeldingen kiezen',
    'instructions' => 'Eén afbeelding wordt over de volledige breedte getoond; twee afbeeldingen komen naast elkaar.',
]);
$fields['logo'] = assets('logo', 'Logo', $vol('logos'), [
    'selectionLabel' => 'Logo kiezen',
    'instructions' => 'Bij voorkeur een SVG- of PNG-bestand met transparante achtergrond.',
]);
$fields['photo'] = assets('photo', 'Foto', $vol('people'), [
    'selectionLabel' => 'Foto kiezen',
    'instructions' => 'Een vierkante foto werkt het best.',
]);
$fields['seoPreviewImage'] = assets('seoPreviewImage', 'Afbeelding voor social media', $vol('media'), [
    'subpath' => 'seo',
    'viewMode' => 'list',
    'instructions' => 'Wordt getoond als de pagina gedeeld wordt op social media. JPEG of PNG, 1200 × 630 px.',
]);

// --- Links & contact ---------------------------------------------------------

$fields['link'] = linkField('link', 'Link', ['entry', 'url', 'email', 'tel'], [
    'showLabelField' => true,
    'advancedFields' => ['target'],
    'instructions' => 'Kies een pagina of vul een URL, e-mailadres of telefoonnummer in.',
    'translate' => 'site',
]);
$fields['websiteUrl'] = linkField('websiteUrl', 'Website-URL', ['url']);
$fields['youtubeUrl'] = linkField('youtubeUrl', 'YouTube-URL', ['url'], [
    'instructions' => 'De volledige link naar de video, bijvoorbeeld https://www.youtube.com/watch?v=… of https://youtu.be/…',
]);
$fields['googleMapsUrl'] = linkField('googleMapsUrl', 'Google Maps-URL', ['url'], [
    'instructions' => 'Link naar de locatie in Google Maps. Het adres wordt hiermee klikbaar.',
]);
$fields['email'] = base('craft\\fields\\Email', 'email', 'E-mailadres', ['placeholder' => 'info@voorbeeld.nl']);
$fields['phone'] = plainText('phone', 'Telefoonnummer', ['placeholder' => '+31 6 12 34 56 78']);
$fields['kvkNumber'] = plainText('kvkNumber', 'KVK-nummer');
$fields['vatId'] = plainText('vatId', 'Btw-nummer', ['placeholder' => 'NL123456789B01']);
$fields['address'] = base('craft\\fields\\Addresses', 'address', 'Adres', [
    'maxAddresses' => 1,
    'minAddresses' => null,
    'viewMode' => 'cards',
], ['translate' => 'site']);

// --- Relaties ----------------------------------------------------------------

$fields['blogs'] = entries('blogs', 'Blogartikelen', ['blogs'], [
    'selectionLabel' => 'Artikelen kiezen',
    'viewMode' => 'list',
    'instructions' => 'Laat leeg om automatisch de nieuwste artikelen te tonen.',
]);
$fields['people'] = entries('people', 'Personen', ['people'], [
    'selectionLabel' => 'Personen kiezen',
    'instructions' => 'Laat leeg om alle personen te tonen.',
]);
$fields['products'] = entries('products', 'Producten', ['products'], [
    'selectionLabel' => 'Producten kiezen',
    'instructions' => 'Laat leeg om alle producten te tonen.',
]);
$fields['testimonials'] = entries('testimonials', 'Testimonials', ['testimonials'], [
    'selectionLabel' => 'Testimonials kiezen',
    'instructions' => 'Laat leeg om alle testimonials te tonen.',
]);
$fields['linkToPage'] = entries('linkToPage', 'Link naar pagina', ['pages'], [
    'max' => 1,
    'selectionLabel' => 'Pagina kiezen',
    'viewMode' => 'list',
]);

// --- Tabellen ----------------------------------------------------------------

$fields['features'] = table('features', 'Kenmerken', [
    ['heading' => 'Kenmerk', 'handle' => 'feature', 'type' => 'singleline'],
], ['addRowLabel' => 'Kenmerk toevoegen']);

$fields['stats'] = table('stats', 'Statistieken', [
    ['heading' => 'Waarde', 'handle' => 'value', 'type' => 'singleline', 'width' => '30%'],
    ['heading' => 'Omschrijving', 'handle' => 'label', 'type' => 'singleline'],
], [
    'addRowLabel' => 'Statistiek toevoegen',
    'instructions' => 'Bijvoorbeeld "250+" en "tevreden klanten".',
]);

// --- Matrixvelden ------------------------------------------------------------

$fields['ctas'] = matrix('ctas', 'Knoppen', ['cta'], [
    'createButtonLabel' => 'Knop toevoegen',
    'viewMode' => 'blocks',
]);
$fields['accordionItems'] = matrix('accordionItems', 'Accordeon-items', ['accordionItem'], [
    'createButtonLabel' => 'Item toevoegen',
    'viewMode' => 'cards',
]);
$fields['cards'] = matrix('cards', 'Kaarten', ['card'], [
    'createButtonLabel' => 'Kaart toevoegen',
    'viewMode' => 'cards',
]);
$fields['columns'] = matrix('columns', 'Kolommen', ['column'], [
    'createButtonLabel' => 'Kolom toevoegen',
    'viewMode' => 'cards',
]);
$fields['tickerItems'] = matrix('tickerItems', 'Ticker-items', ['testimonial', 'image'], [
    'createButtonLabel' => 'Item toevoegen',
    'viewMode' => 'cards',
]);
$fields['items'] = matrix('items', 'Items', ['item'], [
    'createButtonLabel' => 'Item toevoegen',
    'viewMode' => 'blocks',
]);
$fields['body'] = matrix('body', 'Inhoud', ['bodyText', 'bodyImages', 'bodyItems', 'bodyVideo'], [
    'createButtonLabel' => 'Blok toevoegen',
    'viewMode' => 'blocks',
]);

$sectionTypes = [
    'hero', 'text', 'textMedia', 'columns', 'cards', 'accordion', 'stats',
    'testimonials', 'people', 'products', 'companies', 'ticker', 'blogIndex',
    'callToAction', 'embed',
];
$fields['sections'] = matrix('sections', 'Secties', $sectionTypes, [
    'createButtonLabel' => 'Sectie toevoegen',
    'viewMode' => 'cards',
]);

// --- SEO ---------------------------------------------------------------------

$fields['seoTitle'] = plainText('seoTitle', 'SEO-titel', [
    'instructions' => 'De titel in zoekmachines en het browsertabblad. Laat leeg om de paginatitel te gebruiken.',
    'translate' => 'site',
]);
$fields['seoDescription'] = plainText('seoDescription', 'SEO-omschrijving', [
    'multiline' => true,
    'rows' => 3,
    'instructions' => 'Korte omschrijving voor zoekmachines en social media, bij voorkeur maximaal 160 tekens.',
    'translate' => 'site',
]);

foreach ($fields as $handle => $def) {
    $uid = u('field:' . $handle);
    put("$root/fields/$handle--$uid.yaml", $def);
}

// ---------------------------------------------------------------------------
// Entry types
// ---------------------------------------------------------------------------

function entryType(string $key, string $name, array $tabs, array $o = []): array
{
    return [
        'color' => $o['color'] ?? null,
        'description' => $o['description'] ?? null,
        'fieldLayouts' => [
            u('etLayout:' . $key) => [
                'cardThumbAlignment' => 'end',
                'tabs' => tabs('et:' . $key, $tabs, $o['titleOpts'] ?? []),
            ],
        ],
        'handle' => $o['handle'] ?? $key,
        'hasTitleField' => $o['title'] ?? false,
        'icon' => $o['icon'] ?? null,
        'name' => $name,
        'showSlugField' => $o['slug'] ?? false,
        'showStatusField' => $o['status'] ?? true,
        'slugTranslationKeyFormat' => null,
        'slugTranslationMethod' => 'site',
        'titleFormat' => $o['titleFormat'] ?? null,
        'titleTranslationKeyFormat' => null,
        'titleTranslationMethod' => 'site',
    ];
}

$entryTypes = [];

// Shared "Opmaak" tab variants.
$layoutTab = fn(array $extra = []) => array_merge([['theme'], ['isFullWidth']], $extra);
$sectionHeader = [['eyebrow'], 'title', ['richText', ['handle' => 'intro', 'label' => 'Intro']]];

// --- Nested parts ------------------------------------------------------------

$entryTypes['cta'] = entryType('cta', 'Knop', [
    'Inhoud' => [
        ['link'],
        ['style'],
        ['iconName', ['instructions' => 'Optioneel. Naam van een Lucide-icoon, zie https://lucide.dev/icons/']],
    ],
], ['icon' => 'rectangle-horizontal', 'titleFormat' => '{link.label}', 'status' => false]);

$entryTypes['accordionItem'] = entryType('accordionItem', 'Accordeon-item', [
    'Inhoud' => [
        ['eyebrow'],
        'title',
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
    ],
], ['icon' => 'chevron-down', 'title' => true]);

$entryTypes['card'] = entryType('card', 'Kaart', [
    'Inhoud' => [
        ['image'],
        ['eyebrow'],
        'title',
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
        ['ctas', ['instructions' => 'Voeg één knop toe om de hele kaart klikbaar te maken.']],
    ],
], ['icon' => 'square', 'title' => true]);

$entryTypes['column'] = entryType('column', 'Kolom', [
    'Inhoud' => [
        ['image', [
            'instructions' => 'Kies een afbeelding óf een icoon.',
            'condition' => emptyCondition('et:column', 'iconName', 'craft\\fields\\conditions\\TextFieldConditionRule'),
        ]],
        ['iconName', [
            'condition' => emptyCondition('et:column', 'image', 'craft\\fields\\conditions\\RelationalFieldConditionRule'),
        ]],
        'title',
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
        ['ctas'],
    ],
], ['icon' => 'columns-3', 'title' => true]);

$entryTypes['item'] = entryType('item', 'Item', [
    'Inhoud' => [
        'title',
        ['image'],
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
    ],
], ['icon' => 'list', 'title' => true]);

$entryTypes['image'] = entryType('image', 'Afbeelding', [
    'Inhoud' => [['image', ['required' => true]]],
], ['icon' => 'image', 'status' => false]);

$entryTypes['bodyText'] = entryType('bodyText', 'Tekst', [
    'Inhoud' => [['articleText', ['handle' => 'text', 'label' => 'Tekst']]],
], ['icon' => 'text', 'status' => false]);

$entryTypes['bodyImages'] = entryType('bodyImages', 'Afbeelding(en)', [
    'Inhoud' => [['images']],
], ['icon' => 'images', 'status' => false]);

$entryTypes['bodyItems'] = entryType('bodyItems', 'Items', [
    'Inhoud' => [['items', ['instructions' => 'Een lijst met items met een afbeelding, titel en tekst.']]],
], ['icon' => 'list', 'status' => false]);

$entryTypes['bodyVideo'] = entryType('bodyVideo', 'Video', [
    'Inhoud' => [['youtubeUrl', ['required' => true]]],
], ['icon' => 'video', 'status' => false]);

// --- Sections (page building blocks) -----------------------------------------

$entryTypes['hero'] = entryType('hero', 'Hero', [
    'Inhoud' => [
        ['eyebrow'],
        'title',
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
        ['ctas'],
        ['image', ['label' => 'Achtergrondafbeelding', 'instructions' => 'Aanbevolen formaat: 2880 × 1920 px.']],
        ['image', ['handle' => 'imageMobile', 'label' => 'Achtergrondafbeelding (mobiel)', 'instructions' => 'Optioneel. Aanbevolen formaat: 1080 × 1080 px.']],
    ],
    'Opmaak' => [['theme'], ['height'], ['textWidth']],
], ['icon' => 'panel-top', 'title' => true, 'description' => 'De grote openingssectie van een pagina.']);

$entryTypes['text'] = entryType('text', 'Tekst', [
    'Inhoud' => array_merge($sectionHeader, [
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
        ['ctas'],
    ]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'text', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['textMedia'] = entryType('textMedia', 'Tekst & media', [
    'Inhoud' => array_merge($sectionHeader, [
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
        ['ctas'],
        ['image'],
        ['youtubeUrl', ['instructions' => 'Optioneel. Een video vervangt de afbeelding.']],
    ]),
    'Opmaak' => $layoutTab([['textPosition']]),
], ['icon' => 'image', 'title' => true, 'titleOpts' => ['required' => false], 'description' => 'Tekst naast een afbeelding of video.']);

$entryTypes['columns'] = entryType('columns', 'Kolommen', [
    'Inhoud' => array_merge($sectionHeader, [['columns']]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'columns-3', 'title' => true, 'titleOpts' => ['required' => false], 'description' => 'Twee tot vier kolommen met een icoon of afbeelding, titel en tekst.']);

$entryTypes['cards'] = entryType('cards', 'Kaarten', [
    'Inhoud' => array_merge($sectionHeader, [['cards']]),
    'Opmaak' => $layoutTab([['applyThemeTo'], ['sectionHeaderAlignment']]),
], ['icon' => 'layout-grid', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['accordion'] = entryType('accordion', 'Accordeon', [
    'Inhoud' => array_merge($sectionHeader, [['ctas'], ['accordionItems']]),
    'Opmaak' => $layoutTab(),
], ['icon' => 'chevrons-down-up', 'title' => true, 'titleOpts' => ['required' => false], 'description' => 'Uitklapbare items, bijvoorbeeld voor veelgestelde vragen.']);

$entryTypes['stats'] = entryType('stats', 'Statistieken', [
    'Inhoud' => array_merge($sectionHeader, [['stats']]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'chart-no-axes-column', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['testimonials'] = entryType('testimonials', 'Testimonials', [
    'Inhoud' => array_merge($sectionHeader, [['testimonials']]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'quote', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['people'] = entryType('people', 'Personen', [
    'Inhoud' => array_merge($sectionHeader, [['people']]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'users', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['products'] = entryType('products', 'Producten', [
    'Inhoud' => array_merge($sectionHeader, [['products']]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'tag', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['companies'] = entryType('companies', "Bedrijfslogo's", [
    'Inhoud' => $sectionHeader,
    'Opmaak' => $layoutTab(),
], ['icon' => 'building-2', 'title' => true, 'titleOpts' => ['required' => false], 'description' => "Een doorlopende band met de logo's van alle bedrijven."]);

$entryTypes['ticker'] = entryType('ticker', 'Ticker', [
    'Inhoud' => array_merge($sectionHeader, [['tickerItems'], ['rows']]),
    'Opmaak' => $layoutTab(),
], ['icon' => 'gallery-horizontal', 'title' => true, 'titleOpts' => ['required' => false], 'description' => 'Een doorlopende band met afbeeldingen en/of testimonials.']);

$entryTypes['blogIndex'] = entryType('blogIndex', 'Blogoverzicht', [
    'Inhoud' => array_merge($sectionHeader, [['blogs'], ['limit', ['instructions' => 'Alleen van toepassing als er geen artikelen handmatig zijn gekozen. Laat leeg om alle artikelen te tonen (12 per pagina).']]]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'newspaper', 'title' => true, 'titleOpts' => ['required' => false]]);

$entryTypes['callToAction'] = entryType('callToAction', 'Call-to-action', [
    'Inhoud' => [
        'title',
        ['richText', ['handle' => 'text', 'label' => 'Tekst']],
        ['ctas'],
    ],
    'Opmaak' => [['theme']],
], ['icon' => 'megaphone', 'title' => true, 'description' => 'Een opvallend blok met een titel, korte tekst en knop.']);

$entryTypes['embed'] = entryType('embed', 'Embed', [
    'Inhoud' => array_merge($sectionHeader, [
        ['code', ['handle' => 'embedCode', 'label' => 'Embedcode', 'required' => true, 'instructions' => 'De HTML-code van bijvoorbeeld een formulier (MailBlue, Typeform), kaart of agenda.']],
    ]),
    'Opmaak' => $layoutTab([['sectionHeaderAlignment']]),
], ['icon' => 'code', 'title' => true, 'titleOpts' => ['required' => false], 'description' => 'Externe code, bijvoorbeeld een formulier of kaart.']);

// --- Top level ---------------------------------------------------------------

$seoTab = [['seoTitle'], ['seoDescription'], ['seoPreviewImage']];

$entryTypes['page'] = entryType('page', 'Pagina', [
    'Inhoud' => [
        'title',
        ['includeInNav'],
        ['sections'],
    ],
    'SEO' => $seoTab,
], ['icon' => 'file', 'title' => true, 'slug' => true]);

$entryTypes['blog'] = entryType('blog', 'Blogartikel', [
    'Inhoud' => [
        'title',
        ['textarea', ['handle' => 'summary', 'label' => 'Samenvatting', 'instructions' => 'Een korte samenvatting die getoond wordt in overzichten en gebruikt wordt voor SEO.']],
        ['image', ['required' => true, 'instructions' => 'Aanbevolen verhouding 16:9, minimaal 2048 px breed.']],
        ['body'],
    ],
    'SEO' => $seoTab,
], ['icon' => 'newspaper', 'title' => true, 'slug' => true]);

$entryTypes['company'] = entryType('company', 'Bedrijf', [
    'Inhoud' => [
        'title',
        ['logo', ['required' => true]],
        ['websiteUrl'],
    ],
], ['icon' => 'building-2', 'title' => true, 'titleOpts' => ['label' => 'Naam']]);

$entryTypes['person'] = entryType('person', 'Persoon', [
    'Inhoud' => [
        ['photo'],
        'title',
        ['line', ['handle' => 'position', 'label' => 'Functie']],
        ['line', ['handle' => 'company', 'label' => 'Bedrijf']],
        ['line', ['handle' => 'location', 'label' => 'Locatie', 'instructions' => 'Bijvoorbeeld "Utrecht".']],
        ['linkToPage'],
    ],
], ['icon' => 'user', 'title' => true, 'titleOpts' => ['label' => 'Naam']]);

$entryTypes['product'] = entryType('product', 'Product', [
    'Inhoud' => [
        'title',
        ['line', ['handle' => 'price', 'label' => 'Prijs', 'instructions' => 'Alleen het bedrag, bijvoorbeeld "49" of "1.250".']],
        ['fromPrice'],
        ['line', ['handle' => 'priceUnit', 'label' => 'Eenheid', 'instructions' => 'Bijvoorbeeld "maand", "jaar" of "sessie".']],
        ['textarea', ['handle' => 'description', 'label' => 'Omschrijving']],
        ['features'],
        ['ctas'],
    ],
], ['icon' => 'tag', 'title' => true]);

$entryTypes['testimonial'] = entryType('testimonial', 'Testimonial', [
    'Inhoud' => [
        ['richText', ['handle' => 'quote', 'label' => 'Citaat', 'required' => true]],
        'title',
        ['line', ['handle' => 'authorPosition', 'label' => 'Functie']],
        ['line', ['handle' => 'authorCompany', 'label' => 'Bedrijf']],
        ['photo', ['handle' => 'authorPhoto', 'label' => 'Foto']],
    ],
], ['icon' => 'quote', 'title' => true, 'titleOpts' => ['label' => 'Naam', 'required' => false]]);

foreach ($entryTypes as $key => $def) {
    $uid = u('et:' . $key);
    put("$root/entryTypes/{$def['handle']}--$uid.yaml", $def);
}

// ---------------------------------------------------------------------------
// Sections
// ---------------------------------------------------------------------------

$homeUri = "{% if level == 1 and slug == 'home' %}__home__{% else %}{parent.uri}/{slug}{% endif %}";

$sections = [
    // handle, name, type, entry types, uriFormat, template, sortOrder, maxLevels
    ['pages', "Pagina's", 'structure', ['page'], $homeUri, 'pages/_entry', 2],
    ['legal', 'Juridisch', 'structure', ['page'], '{slug}', 'pages/_entry', 1],
    ['blogs', 'Blog', 'channel', ['blog'], 'blog/{slug}', 'blog/_entry', null],
    ['people', 'Personen', 'structure', ['person'], null, null, 1],
    ['testimonials', 'Testimonials', 'structure', ['testimonial'], null, null, 1],
    ['companies', 'Bedrijven', 'structure', ['company'], null, null, 1],
    ['products', 'Producten', 'structure', ['product'], null, null, 1],
];

foreach ($sections as [$handle, $name, $type, $etKeys, $uriFormat, $template, $maxLevels]) {
    $uid = u('section:' . $handle);
    $def = [
        'defaultPlacement' => 'end',
        'enableVersioning' => true,
        'entryTypes' => array_map(fn($k) => ['uid' => u('et:' . $k)], $etKeys),
        'handle' => $handle,
        'maxAuthors' => 1,
        'minAuthors' => 1,
        'name' => $name,
        'previewTargets' => $uriFormat ? [[
            'label' => 'Pagina',
            'refresh' => '1',
            'urlFormat' => '{url}',
        ]] : [],
        'propagationMethod' => 'all',
        'siteSettings' => [
            $siteUid => [
                'enabledByDefault' => true,
                'hasUrls' => $uriFormat !== null,
                'template' => $template,
                'uriFormat' => $uriFormat,
            ],
        ],
        'type' => $type,
    ];
    if ($type === 'structure') {
        $def['structure'] = ['maxLevels' => $maxLevels, 'uid' => u('structure:' . $handle)];
    }
    put("$root/sections/$handle--$uid.yaml", $def);
}

// ---------------------------------------------------------------------------
// Global sets
// ---------------------------------------------------------------------------

$globals = [
    ['brand', 'Huisstijl', 1, [
        ['logo', ['handle' => 'logoNav', 'label' => 'Logo (navigatie)', 'instructions' => 'Wordt getoond in de navigatiebalk.']],
        ['logo', ['handle' => 'logoFooter', 'label' => 'Logo (footer)', 'instructions' => 'Optioneel. Bijvoorbeeld een lichte variant voor de donkere footer. Laat leeg om het navigatielogo te gebruiken.']],
        ['richText', ['handle' => 'footerText', 'label' => 'Footertekst', 'instructions' => 'Korte tekst over het bedrijf, onder het logo in de footer.']],
        ['seoPreviewImage', ['instructions' => 'Standaardafbeelding voor social media, voor pagina\'s zonder eigen afbeelding. JPEG of PNG, 1200 × 630 px.']],
    ]],
    ['contact', 'Contactgegevens', 2, [
        ['address'],
        ['email'],
        ['phone'],
        ['enableWhatsapp'],
        ['kvkNumber'],
        ['vatId'],
        ['heading', 'Social media'],
        ['websiteUrl', ['handle' => 'instagramUrl', 'label' => 'Instagram']],
        ['websiteUrl', ['handle' => 'facebookUrl', 'label' => 'Facebook']],
        ['websiteUrl', ['handle' => 'linkedinUrl', 'label' => 'LinkedIn']],
        ['websiteUrl', ['handle' => 'xUrl', 'label' => 'X']],
        ['websiteUrl', ['handle' => 'youtubeChannelUrl', 'label' => 'YouTube']],
        ['websiteUrl', ['handle' => 'tiktokUrl', 'label' => 'TikTok']],
    ]],
    ['navigation', 'Navigatie', 3, [
        ['ctas', ['instructions' => 'Knoppen rechts in de navigatiebalk, bijvoorbeeld "Contact" of "Afspraak maken". Het menu zelf volgt automatisch de pagina\'s.']],
    ]],
    ['settings', 'Instellingen', 4, [
        ['useParallax'],
        ['code', ['handle' => 'htmlHead', 'label' => 'HTML in <head>', 'instructions' => 'Voor bijvoorbeeld statistieken of verificatiecodes. Wordt op elke pagina geplaatst.']],
    ]],
];

foreach ($globals as [$handle, $name, $sort, $elements]) {
    $uid = u('globalSet:' . $handle);
    put("$root/globalSets/$handle--$uid.yaml", [
        'fieldLayouts' => [
            u('globalLayout:' . $handle) => [
                'cardThumbAlignment' => 'end',
                'tabs' => tabs('global:' . $handle, [$name => $elements]),
            ],
        ],
        'handle' => $handle,
        'name' => $name,
        'sortOrder' => $sort,
    ]);
}

// ---------------------------------------------------------------------------
// Users (blog authors) and addresses
// ---------------------------------------------------------------------------

$usersLayoutUid = u('usersLayout');
put("$root/users/fieldLayouts/$usersLayoutUid.yaml", [
    'cardThumbAlignment' => 'end',
    'tabs' => [[
        'elementCondition' => null,
        'elements' => [
            nativeTextEl('users', 'craft\\fieldlayoutelements\\users\\UsernameField', 'username'),
            nativeTextEl('users', 'craft\\fieldlayoutelements\\users\\FullNameField', 'fullName', ['attribute' => 'fullName', 'requirable' => true, 'required' => false]),
            [
                'dateAdded' => $now,
                'elementCondition' => null,
                'id' => null,
                'instructions' => null,
                'label' => null,
                'orientation' => null,
                'requirable' => false,
                'tip' => null,
                'type' => 'craft\\fieldlayoutelements\\users\\PhotoField',
                'uid' => u('layoutel:users:photo'),
                'userCondition' => null,
                'warning' => null,
                'width' => 100,
            ],
            nativeTextEl('users', 'craft\\fieldlayoutelements\\users\\EmailField', 'email'),
            fld('users', 'richText', ['handle' => 'bio', 'label' => 'Bio', 'instructions' => 'Wordt getoond onder blogartikelen van deze auteur.']),
            fld('users', 'websiteUrl', ['handle' => 'linkedinUrl', 'label' => 'LinkedIn']),
        ],
        'name' => 'Profiel',
        'uid' => u('tab:users'),
        'userCondition' => null,
    ]],
]);

put("$root/users/users.yaml", [
    'allowPublicRegistration' => false,
    'defaultGroup' => null,
    'photoSubpath' => 'gebruikers',
    'photoVolumeUid' => u('volume:people'),
    'require2fa' => false,
    'requireEmailVerification' => true,
]);

$addressesLayoutUid = u('addressesLayout');
put("$root/addresses/fieldLayouts/$addressesLayoutUid.yaml", [
    'cardThumbAlignment' => 'end',
    'tabs' => [[
        'elementCondition' => null,
        'elements' => [
            nativeTextEl('addresses', 'craft\\fieldlayoutelements\\addresses\\LabelField', 'label', [
                'instructions' => 'Optioneel, bijvoorbeeld "Kantoor" of "Werkplaats".',
                'required' => false,
            ]),
            [
                'attribute' => 'countryCode',
                'dateAdded' => $now,
                'elementCondition' => null,
                'id' => null,
                'instructions' => null,
                'label' => null,
                'orientation' => null,
                'requirable' => false,
                'tip' => null,
                'type' => 'craft\\fieldlayoutelements\\addresses\\CountryCodeField',
                'uid' => u('layoutel:addresses:countryCode'),
                'userCondition' => null,
                'warning' => null,
                'width' => 100,
            ],
            [
                'dateAdded' => $now,
                'elementCondition' => null,
                'instructions' => null,
                'label' => null,
                'required' => false,
                'tip' => null,
                'type' => 'craft\\fieldlayoutelements\\addresses\\AddressField',
                'uid' => u('layoutel:addresses:address'),
                'userCondition' => null,
                'warning' => null,
            ],
            fld('addresses', 'googleMapsUrl'),
        ],
        'name' => 'Adres',
        'uid' => u('tab:addresses'),
        'userCondition' => null,
    ]],
]);

// ---------------------------------------------------------------------------
// project.yaml
// ---------------------------------------------------------------------------

$names = [];
$names[$siteUid] = 'Boilerplate';
$names[$siteGroupUid] = 'Boilerplate';
foreach ($volumes as [$h, $n]) {
    $names[u('volume:' . $h)] = $n;
}
foreach ($fields as $h => $def) {
    $names[u('field:' . $h)] = $def['name'];
}
foreach ($entryTypes as $k => $def) {
    $names[u('et:' . $k)] = $def['name'];
}
foreach ($sections as [$h, $n]) {
    $names[u('section:' . $h)] = $n;
}
foreach ($globals as [$h, $n]) {
    $names[u('globalSet:' . $h)] = $n;
}

$source = fn(string $handle, array $attrs) => [
    'defaultSort' => ['structure', 'asc'],
    'defaultViewMode' => '',
    'disabled' => false,
    'key' => 'section:' . u('section:' . $handle),
    'tableAttributes' => $attrs,
    'type' => 'native',
];

$elementSources = [
    ['key' => '*', 'type' => 'native'],
    $source('pages', ['slug', 'status', 'link']),
    $source('legal', ['slug', 'status', 'link']),
    [
        'defaultSort' => ['postDate', 'desc'],
        'defaultViewMode' => '',
        'disabled' => false,
        'key' => 'section:' . u('section:blogs'),
        'tableAttributes' => ['status', 'postDate', 'author', 'link'],
        'type' => 'native',
    ],
    ['heading' => 'Inhoud', 'key' => 'heading:' . u('sourceHeading:content'), 'type' => 'heading'],
    $source('people', ['status', 'field:' . u('field:photo')]),
    $source('testimonials', ['status']),
    $source('companies', ['status', 'field:' . u('field:logo')]),
    $source('products', ['status']),
];

$existing = file_exists("$root/project.yaml") ? Yaml::parseFile("$root/project.yaml") : [];

put("$root/project.yaml", [
    'dateModified' => time(),
    'elementSources' => ['craft\\elements\\Entry' => $elementSources],
    'email' => $existing['email'] ?? [
        'fromEmail' => 'mail@oscaralexander.com',
        'fromName' => 'Boilerplate',
        'transportType' => 'craft\\mail\\transportadapters\\Sendmail',
    ],
    'fs' => [
        'local' => [
            'hasUrls' => true,
            'name' => 'Uploads',
            'settings' => ['path' => '@webroot/uploads'],
            'type' => 'craft\\fs\\Local',
            'url' => '@web/uploads',
        ],
    ],
    'meta' => ['__names__' => $names],
    'plugins' => $existing['plugins'] ?? [],
    'system' => [
        'edition' => 'solo',
        'live' => true,
        'name' => 'Boilerplate',
        'schemaVersion' => $existing['system']['schemaVersion'] ?? '5.11.0.2',
        'timeZone' => 'Europe/Amsterdam',
    ],
]);

echo "Wrote project config to $root\n";
