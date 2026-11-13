<?php

/*
 * Controlled tattoo style taxonomy (IT/EN).
 * Synonyms are lowercase and used for text matching (query parser, Phase 2).
 * Children inherit their parent's meaning: filtering by a parent includes them.
 */

return [
    ['slug' => 'fine-line', 'it' => 'Fine line', 'en' => 'Fine line', 'synonyms' => ['fineline', 'linea sottile', 'linee sottili', 'single needle', 'minimal', 'minimalista']],
    ['slug' => 'blackwork', 'it' => 'Blackwork', 'en' => 'Blackwork', 'synonyms' => ['black work', 'nero pieno', 'total black'], 'children' => [
        ['slug' => 'dotwork', 'it' => 'Dotwork', 'en' => 'Dotwork', 'synonyms' => ['dot work', 'puntinato', 'puntinismo', 'stippling']],
        ['slug' => 'geometric', 'it' => 'Geometrico', 'en' => 'Geometric', 'synonyms' => ['geometria', 'geometria sacra', 'sacred geometry']],
        ['slug' => 'ornamental', 'it' => 'Ornamentale', 'en' => 'Ornamental', 'synonyms' => ['mandala', 'ornamenti', 'decorativo']],
        ['slug' => 'tribal', 'it' => 'Tribale', 'en' => 'Tribal', 'synonyms' => ['tribal', 'polinesiano', 'polynesian', 'maori']],
    ]],
    ['slug' => 'realism', 'it' => 'Realistico', 'en' => 'Realism', 'synonyms' => ['realismo', 'realistic', 'iperrealismo', 'hyperrealism'], 'children' => [
        ['slug' => 'micro-realism', 'it' => 'Micro realismo', 'en' => 'Micro realism', 'synonyms' => ['microrealismo', 'micro realistico', 'microrealism']],
        ['slug' => 'portrait', 'it' => 'Ritratto', 'en' => 'Portrait', 'synonyms' => ['ritratti', 'portraits', 'volto']],
    ]],
    ['slug' => 'black-and-grey', 'it' => 'Black & grey', 'en' => 'Black & grey', 'synonyms' => ['black and grey', 'black & gray', 'b&g', 'bianco e nero', 'scala di grigi'], 'children' => [
        ['slug' => 'chicano', 'it' => 'Chicano', 'en' => 'Chicano', 'synonyms' => ['chicano style']],
    ]],
    ['slug' => 'traditional', 'it' => 'Tradizionale', 'en' => 'Traditional', 'synonyms' => ['tradizionale', 'traditional'], 'children' => [
        ['slug' => 'old-school', 'it' => 'Old school', 'en' => 'Old school', 'synonyms' => ['oldschool', 'american traditional', 'tradizionale americano', 'sailor jerry']],
        ['slug' => 'neo-traditional', 'it' => 'Neo traditional', 'en' => 'Neo traditional', 'synonyms' => ['neotraditional', 'neo tradizionale', 'neotradizionale']],
    ]],
    ['slug' => 'japanese', 'it' => 'Giapponese', 'en' => 'Japanese', 'synonyms' => ['irezumi', 'japanese', 'orientale', 'wabori']],
    ['slug' => 'new-school', 'it' => 'New school', 'en' => 'New school', 'synonyms' => ['newschool', 'cartoon', 'fumetto', 'anime']],
    ['slug' => 'illustrative', 'it' => 'Illustrativo', 'en' => 'Illustrative', 'synonyms' => ['illustrativa', 'illustration', 'sketch', 'schizzo']],
    ['slug' => 'watercolor', 'it' => 'Acquerello', 'en' => 'Watercolor', 'synonyms' => ['acquarello', 'watercolour', 'water color']],
    ['slug' => 'lettering', 'it' => 'Lettering', 'en' => 'Lettering', 'synonyms' => ['scritte', 'scritta', 'calligrafia', 'calligraphy', 'script', 'corsivo']],
    ['slug' => 'trash-polka', 'it' => 'Trash polka', 'en' => 'Trash polka', 'synonyms' => ['trashpolka']],
    ['slug' => 'cover-up', 'it' => 'Cover-up', 'en' => 'Cover-up', 'synonyms' => ['cover up', 'copertura', 'coprire tatuaggio']],
];
