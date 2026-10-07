<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/PrintSettings.php';

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: private, max-age=300');

try {
    $settings = PrintSettings::normalize($_GET);
} catch (Throwable) {
    http_response_code(400);
    echo "/* réglages d'impression invalides */";
    exit;
}

$estimatedPages = max(1, min(5000, (int) ($_GET['estimated_pages'] ?? 1)));
[$pageWidth, $pageHeight] = PrintSettings::trimDimensions($settings['trim_size']);
$gutter = $settings['gutter_mode'] === 'custom'
    ? (float) $settings['gutter_mm']
    : PrintSettings::automaticGutter($estimatedPages, $settings['binding']);
$outerMargin = 14.0;
$chapterBreak = $settings['chapter_start'] === 'right' ? 'right' : 'page';
$resetBodyCounter = $settings['front_matter_numbering'] === 'arabic' ? '' : 'counter-reset: page 1;';
$frontCounter = $settings['front_matter_numbering'] === 'roman' ? 'counter(page, lower-roman)' : 'counter(page)';
$frontContent = $settings['front_matter_numbering'] === 'hidden' ? 'none' : $frontCounter;
$bodyContent = 'counter(page)';
$bleed = (int) $settings['bleed_mm'] === 3 ? "bleed:3mm;marks:crop;" : '';

echo "@page{size:{$pageWidth}mm {$pageHeight}mm;margin-top:16mm;margin-bottom:18mm;{$bleed}}\n";
echo "@page:left{margin-left:{$outerMargin}mm;margin-right:{$gutter}mm}\n";
echo "@page:right{margin-left:{$gutter}mm;margin-right:{$outerMargin}mm}\n";
echo "@page:blank{@bottom-left{content:none}@bottom-center{content:none}@bottom-right{content:none}}\n";

if ($settings['page_number_position'] === 'center') {
    echo "@page frontmatter{@bottom-center{content:{$frontContent}}}\n";
    echo "@page chapter{@bottom-center{content:{$bodyContent}}}\n";
} elseif ($settings['page_number_position'] === 'outside') {
    echo "@page frontmatter:left{@bottom-left{content:{$frontContent}}@bottom-right{content:none}}\n";
    echo "@page frontmatter:right{@bottom-right{content:{$frontContent}}@bottom-left{content:none}}\n";
    echo "@page chapter:left{@bottom-left{content:{$bodyContent}}@bottom-right{content:none}}\n";
    echo "@page chapter:right{@bottom-right{content:{$bodyContent}}@bottom-left{content:none}}\n";
}

if ((int) $settings['hide_chapter_openers'] === 1) {
    echo "@page chapter:first{@bottom-left{content:none}@bottom-center{content:none}@bottom-right{content:none}}\n";
}

echo ".chapter{break-before:{$chapterBreak}}\n";
echo ".chapter.first-chapter{{$resetBodyCounter}}\n";
