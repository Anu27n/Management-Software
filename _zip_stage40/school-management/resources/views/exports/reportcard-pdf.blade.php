<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 4mm;
        }
        body {
            margin: 0;
            color: #1a1309;
            font-family: DejaVu Serif, serif;
            font-size: 9px;
            line-height: 1.22;
        }
        .progress-sheet {
            width: 100%;
            color: #1a1309;
        }
        .progress-sheet__frame {
            background: #fffef8;
            border: 1.4px solid #4b3723;
            padding: 4px;
        }
        .progress-sheet__header {
            border: 1px solid #4b3723;
            border-bottom: none;
        }
        .progress-sheet__brand {
            text-align: center;
            padding: 7px 10px 5px;
        }
        .progress-sheet__meta,
        .progress-sheet__layout-table,
        .progress-sheet__table,
        .progress-sheet__side-table,
        .progress-sheet__scale-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .progress-sheet__logo {
            max-height: 50px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
            margin-right: 10px;
        }
        .progress-sheet__heading {
            display: inline-block;
            max-width: 88%;
            vertical-align: middle;
            text-align: center;
        }
        .progress-sheet__school {
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.02;
        }
        .progress-sheet__subhead {
            font-size: 8.4px;
            line-height: 1.2;
        }
        .progress-sheet__title {
            border-top: 1px solid #4b3723;
            background: #f1e7d8;
            text-align: center;
            font-size: 9.2px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 4px 6px;
            letter-spacing: 0.25px;
        }
        .progress-sheet__meta td,
        .progress-sheet__table th,
        .progress-sheet__table td,
        .progress-sheet__side-table th,
        .progress-sheet__side-table td,
        .progress-sheet__scale-table th,
        .progress-sheet__scale-table td {
            border: 1px solid #4b3723;
            padding: 3.2px 4.2px;
            vertical-align: middle;
        }
        .progress-sheet__meta td {
            font-size: 8.4px;
            width: 33.33%;
        }
        .progress-sheet__layout-table td {
            padding: 0;
            vertical-align: top;
            border: none;
        }
        .progress-sheet__layout-main {
            width: 70%;
            padding-right: 4px !important;
        }
        .progress-sheet__layout-side {
            width: 30%;
            padding-left: 4px !important;
        }
        .progress-sheet__panel--spaced {
            margin-top: 10px;
        }
        .progress-sheet__panel {
            border: 1px solid #4b3723;
            background: #fffef8;
        }
        .progress-sheet__panel + .progress-sheet__panel {
            margin-top: 4px;
        }
        .progress-sheet__panel--stretch {
            height: auto;
        }
        .progress-sheet__right-stack {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
            table-layout: fixed;
        }
        .progress-sheet__right-stack-cell {
            padding: 0;
            vertical-align: top;
        }
        .progress-sheet__section-title {
            background: #f1e7d8;
            border-bottom: 1px solid #4b3723;
            text-align: center;
            font-size: 7.8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.25px;
            padding: 3px 4px;
        }
        .progress-sheet__table th,
        .progress-sheet__side-table th,
        .progress-sheet__scale-table th {
            background: #f7f1e7;
            text-transform: uppercase;
            font-size: 6.9px;
            font-weight: 700;
            text-align: center;
        }
        .progress-sheet__table td,
        .progress-sheet__side-table td,
        .progress-sheet__scale-table td {
            font-size: 7.7px;
            line-height: 1.26;
            text-align: center;
        }
        .progress-sheet__table .subject-col,
        .progress-sheet__side-table td:first-child {
            text-align: left;
            font-weight: 700;
        }
        .progress-sheet__table .subject-col {
            width: auto;
        }
        .progress-sheet__table tfoot td,
        .progress-sheet__side-table tfoot td,
        .progress-sheet__scale-table tfoot td {
            font-weight: 700;
        }
        .progress-sheet__remarks-body {
            padding: 6px 7px;
            font-size: 7.6px;
            line-height: 1.36;
        }
        .progress-sheet__remarks-body--expanded {
            min-height: 0;
        }
        .progress-sheet__footer-wrap {
            border-top: 1px solid #4b3723;
            margin-top: 20px;
            padding: 18px 10px 12px;
        }
        .progress-sheet__footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .progress-sheet__footer-table td {
            width: 33.33%;
            padding: 4px 10px 0;
            text-align: center;
            vertical-align: bottom;
            font-size: 8px;
            font-weight: 700;
        }
        .progress-sheet__sign-line {
            border-top: 1.6px solid #1a1309;
            margin: 0 auto 6px;
            width: 90%;
        }
        .progress-sheet__sign-label {
            display: inline-block;
            padding-top: 2px;
            letter-spacing: 0.1px;
        }
    </style>
</head>
<body>
@php
    $logoPath = $siteSettings->logo_path ? storage_path('app/public/' . ltrim($siteSettings->logo_path, '/')) : null;
    $logoDataUri = null;

    if ((!$logoPath || !file_exists($logoPath)) && !empty($siteSettings->logo_url)) {
        $logoUrlPath = parse_url($siteSettings->logo_url, PHP_URL_PATH);

        if (is_string($logoUrlPath) && $logoUrlPath !== '') {
            $normalizedPath = ltrim(str_replace('\\', '/', $logoUrlPath), '/');
            $publicCandidate = public_path($normalizedPath);
            $storageCandidate = storage_path('app/public/' . preg_replace('#^storage/#', '', $normalizedPath));

            if (file_exists($publicCandidate)) {
                $logoPath = $publicCandidate;
            } elseif (file_exists($storageCandidate)) {
                $logoPath = $storageCandidate;
            }
        }
    }

    if ((!$logoPath || !file_exists($logoPath))) {
        $workspaceFallbackLogo = dirname(base_path()) . DIRECTORY_SEPARATOR . 'schoollogo.png';

        if (file_exists($workspaceFallbackLogo)) {
            $logoPath = $workspaceFallbackLogo;
        }
    }

    if ($logoPath && file_exists($logoPath)) {
        $mimeType = function_exists('mime_content_type') ? mime_content_type($logoPath) : 'image/png';
        $logoContents = @file_get_contents($logoPath);

        if ($logoContents !== false) {
            $logoDataUri = 'data:' . ($mimeType ?: 'image/png') . ';base64,' . base64_encode($logoContents);
        }
    }

    if ($logoDataUri) {
        $siteSettings->logo_url = $logoDataUri;
    } else {
        $siteSettings->logo_url = null;
    }
@endphp

@include('reportcards.partials.marksheet', ['marksheet' => $marksheet, 'forPdf' => true])
</body>
</html>
