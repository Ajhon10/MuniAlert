<?php

require dirname(__DIR__).'/hazard-server/vendor/autoload.php';

set_error_handler(function ($severity, $message, $file, $line) {
    // The locked PHPWord targets the project's PHP 8.2 baseline. PHP 8.5 emits
    // vendor deprecations; content/XML warnings must still fail this export.
    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Settings;

$documents = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$word = new PhpWord;
Settings::setOutputEscapingEnabled(true);
$word->setDefaultFontName('Times New Roman');
$word->setDefaultFontSize(12);
$word->setDefaultParagraphStyle(['spaceAfter' => 160, 'lineHeight' => 1.5]);
$word->addTitleStyle(1, ['name' => 'Times New Roman', 'size' => 16, 'bold' => true], ['spaceBefore' => 240, 'spaceAfter' => 240]);
$word->addTitleStyle(2, ['name' => 'Times New Roman', 'size' => 14, 'bold' => true], ['spaceBefore' => 200, 'spaceAfter' => 160]);
$word->addTitleStyle(3, ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], ['spaceBefore' => 160]);
$word->getDocInfo()->setTitle('MuniAlert Capstone — Chapters 1–5');
$word->getDocInfo()->setSubject('Editable developmental capstone draft; institutional details and field evaluation pending');
foreach ($documents as $index => $document) {
    $section = $word->addSection(['paperSize' => 'A4', 'marginTop' => 1440, 'marginBottom' => 1440, 'marginLeft' => 1440, 'marginRight' => 1440]);
    $section->addFooter()->addPreserveText('MuniAlert · Capstone draft · {PAGE}', ['size' => 9], ['alignment' => 'center']);
    Html::addHtml($section, $document['html'], false, false);
    if ($index === 0) {
        $contents = $word->addSection(['paperSize' => 'A4', 'marginTop' => 1440, 'marginBottom' => 1440, 'marginLeft' => 1440, 'marginRight' => 1440]);
        $contents->addText('CONTENTS', ['bold' => true, 'size' => 16]);
        $contents->addText('Update the table of contents in Word after editing. Fields, names and evaluation entries are intentionally editable.');
        $contents->addTOC(['name' => 'Times New Roman', 'size' => 11], [], 1, 2);
    }
}
IOFactory::createWriter($word, 'Word2007')->save($argv[2]);
$archive = new ZipArchive;
if ($archive->open($argv[2]) !== true) {
    throw new RuntimeException('Generated Word archive could not be opened.');
}
$document = $archive->getFromName('word/document.xml');
$archive->close();
$xml = new DOMDocument;
if (! $xml->loadXML($document, LIBXML_NONET)) {
    throw new RuntimeException('Generated Word document XML is invalid.');
}
foreach (['School / college', 'Abstract', 'CHAPTER 1', 'CHAPTER 2', 'CHAPTER 3', 'CHAPTER 4', 'CHAPTER 5'] as $required) {
    if (! str_contains($document, $required)) {
        throw new RuntimeException('Generated Word document is missing required content.');
    }
}
