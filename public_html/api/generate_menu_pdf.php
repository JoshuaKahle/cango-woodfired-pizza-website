<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);

if (ob_get_level() === 0) {
    ob_start();
}

require_once '../config/db.php';
require_once '../includes/functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

require_login_api();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Invalid Request'], 405);
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoloadPath)) {
    json_response([
        'error' => 'PDF generator dependencies are missing. Run composer install in public_html/.',
        'details' => 'Missing: ' . $autoloadPath
    ], 500);
}

require_once $autoloadPath;

function mm_to_points($mm) {
    return ((float)$mm) * 72 / 25.4;
}

function format_rand_no_decimals($value) {
    if ($value === null || $value === '') return '';
    return number_format((float)$value, 0, '.', '');
}

function get_columns_for_category($category, $allSizes) {
    $columns = [];
    $has_config = !empty($category['allowed_variants']);

    if ($has_config) {
        foreach ($category['allowed_variants'] as $size_id) {
            foreach ($allSizes as $def) {
                if ((int)$def['id'] === (int)$size_id) {
                    $columns[] = $def;
                    break;
                }
            }
        }
        return $columns;
    }

    $unique_ids = [];
    if (!empty($category['items'])) {
        foreach ($category['items'] as $item) {
            if (!empty($item['variants'])) {
                foreach ($item['variants'] as $v) {
                    if (!in_array($v['size_id'], $unique_ids)) {
                        $unique_ids[] = $v['size_id'];
                    }
                }
            }
        }
    }

    foreach ($unique_ids as $sid) {
        foreach ($allSizes as $def) {
            if ((int)$def['id'] === (int)$sid) {
                $columns[] = $def;
                break;
            }
        }
    }

    return $columns;
}

function img_to_data_uri($absolutePath) {
    if (!is_file($absolutePath)) return '';
    $data = file_get_contents($absolutePath);
    if ($data === false) return '';

    $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
    $mime = 'image/png';
    if ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
    if ($ext === 'gif') $mime = 'image/gif';
    if ($ext === 'webp') $mime = 'image/webp';

    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

function format_css_number($value) {
    return rtrim(rtrim(number_format((float)$value, 3, '.', ''), '0'), '.');
}

try {
    $allSizes = get_size_definitions($pdo);
    $menuCategories = get_full_menu($pdo, 'menu');
    $specialCategories = get_full_menu($pdo, 'special');

    $pdf_width_mm = get_setting($pdo, 'pdf_page_width_mm', '148');
    $pdf_height_mm = get_setting($pdo, 'pdf_page_height_mm', '230');

    if (!is_numeric($pdf_width_mm) || (float)$pdf_width_mm <= 0) {
        $pdf_width_mm = '148';
    }
    if (!is_numeric($pdf_height_mm) || (float)$pdf_height_mm <= 0) {
        $pdf_height_mm = '230';
    }

    // Use full A5 height to maximize content per page.
    if ((float)$pdf_height_mm < 210) {
        $pdf_height_mm = '210';
    }

    // If the settings are still at the previous smaller default, bump to A5 to better fit a 2-page menu.
    if ((string)$pdf_width_mm === '140' && (string)$pdf_height_mm === '200') {
        $pdf_width_mm = '148';
        $pdf_height_mm = '210';
    }

    $logoPath = __DIR__ . '/../assets/images/logo_pdf.png';
    $logoDataUri = img_to_data_uri($logoPath);

    $businessHours = 'MON - SAT: 10AM - 9PM | SUN: CLOSED';
    $businessAddress = 'Langenhoven Str. 187 Oudtshoorn';
    $businessPhone = '044 272 0708 / 9';

    $trimWidthMm = (float)$pdf_width_mm;
    $trimHeightMm = (float)$pdf_height_mm;
    $cropMarginMm = 4.0;
    $cropMarkLenMm = 3.0;

    $baseMarginTopMm = 5.2;
    $baseMarginRightMm = 4.8;
    $baseMarginBottomMm = 5.2;
    $baseMarginLeftMm = 4.8;

    $pageMarginTopMm = $cropMarginMm + $baseMarginTopMm;
    $pageMarginRightMm = $cropMarginMm + $baseMarginRightMm;
    $pageMarginBottomMm = $cropMarginMm + $baseMarginBottomMm;
    $pageMarginLeftMm = $cropMarginMm + $baseMarginLeftMm;

    $bleedXmm = $cropMarginMm + $baseMarginLeftMm;

    $bodyHtml = '';
    $bodyHtml .= '<div class="crop tl"></div><div class="crop tr"></div><div class="crop bl"></div><div class="crop br"></div>';

    $bodyHtml .= '<table class="header-table"><tr>';
    $bodyHtml .= '<td style="width: 45mm;">';
    if ($logoDataUri !== '') {
        $bodyHtml .= '<img class="logo" src="' . $logoDataUri . '" alt="Cango Pizza">';
    }
    $bodyHtml .= '</td>';
    $bodyHtml .= '<td style="text-align: right;">';
    $bodyHtml .= '<p class="biz-name">CANGO WOODFIRED PIZZA</p>';
    $bodyHtml .= '<p class="biz-line">' . h($businessHours) . '</p>';
    $bodyHtml .= '<p class="biz-line">' . h($businessAddress) . '</p>';
    $bodyHtml .= '<p class="biz-phone">ORDER: ' . h($businessPhone) . '</p>';
    $bodyHtml .= '<p class="promo">Free Delivery (R150+)</p>';
    $bodyHtml .= '</td>';
    $bodyHtml .= '</tr></table>';
    $bodyHtml .= '<div class="rule"></div>';

    foreach ($menuCategories as $category) {
        if (empty($category['items'])) continue;

        $columns = get_columns_for_category($category, $allSizes);
        $show_meas = isset($category['show_measurements']) ? (int)$category['show_measurements'] : 1;

        $bodyHtml .= '<table class="menu-table"><thead><tr>';
        $bodyHtml .= '<th class="left category">' . h($category['name']) . '</th>';

        if (empty($columns)) {
            $bodyHtml .= '<th>Price</th>';
        } else {
            foreach ($columns as $col) {
                $colLabel = (count($columns) === 1 && strtolower((string)$col['name']) === 'standard') ? 'Price' : $col['name'];
                $bodyHtml .= '<th>' . h($colLabel);
                if ($show_meas && $colLabel !== 'Price' && !empty($col['measurement'])) {
                    $bodyHtml .= '&nbsp;<span class="small">' . h($col['measurement']) . '</span>';
                }
                $bodyHtml .= '</th>';
            }
        }

        $bodyHtml .= '</tr></thead><tbody>';

        foreach ($category['items'] as $item) {
            $bodyHtml .= '<tr>';
            $bodyHtml .= '<td class="item">';
            $bodyHtml .= '<div class="item-name">' . h($item['name']) . '</div>';
            if (!empty($item['description'])) {
                $bodyHtml .= '<div class="item-desc">' . h($item['description']) . '</div>';
            }
            $bodyHtml .= '</td>';

            if (empty($columns)) {
                $priceCell = '-';
                if (!empty($item['variants'])) {
                    if (count($item['variants']) === 1) {
                        $priceCell = 'R' . format_rand_no_decimals($item['variants'][0]['price']);
                    } else {
                        $priceCell = 'R' . format_rand_no_decimals($item['min_price']) . '+';
                    }
                }
                $bodyHtml .= '<td class="price">' . h($priceCell) . '</td>';
            } else {
                foreach ($columns as $col) {
                    $price = '-';
                    foreach ($item['variants'] as $v) {
                        if ((int)$v['size_id'] === (int)$col['id']) {
                            $price = 'R' . format_rand_no_decimals($v['price']);
                            break;
                        }
                    }
                    $bodyHtml .= '<td class="price">' . h($price) . '</td>';
                }
            }

            $bodyHtml .= '</tr>';
        }

        $bodyHtml .= '</tbody></table>';
    }

    if (!empty($specialCategories)) {
        $bodyHtml .= '<div class="specials-heading">SPECIALS</div>';

        $today = date('D');

        foreach ($specialCategories as $category) {
            if (empty($category['items'])) continue;

            $columns = get_columns_for_category($category, $allSizes);
            $show_meas = isset($category['show_measurements']) ? (int)$category['show_measurements'] : 1;

            $activeDaysStr = 'Every Day';
            if (!empty($category['active_days']) && is_array($category['active_days'])) {
                $activeDaysStr = implode(', ', $category['active_days']);
                if (in_array($today, $category['active_days'])) {
                    $activeDaysStr .= ' (Active Today)';
                }
            }

            $bodyHtml .= '<table class="menu-table"><thead><tr>';
            $bodyHtml .= '<th class="left category">' . h($category['name']) . ' <span class="days">' . h($activeDaysStr) . '</span></th>';

            if (empty($columns) || count($columns) < 2) {
                $bodyHtml .= '<th>Price</th>';
            } else {
                foreach ($columns as $col) {
                    $colLabel = (count($columns) === 1 && strtolower((string)$col['name']) === 'standard') ? 'Price' : $col['name'];
                    $bodyHtml .= '<th>' . h($colLabel);
                    if ($show_meas && $colLabel !== 'Price' && !empty($col['measurement'])) {
                        $bodyHtml .= '&nbsp;<span class="small">' . h($col['measurement']) . '</span>';
                    }
                    $bodyHtml .= '</th>';
                }
            }

            $bodyHtml .= '</tr></thead><tbody>';

            foreach ($category['items'] as $item) {
                $bodyHtml .= '<tr>';
                $bodyHtml .= '<td class="item">';
                $bodyHtml .= '<div class="item-name">' . nl2br(h($item['name'])) . '</div>';
                if (!empty($item['description'])) {
                    $bodyHtml .= '<div class="item-desc">' . h($item['description']) . '</div>';
                }
                $bodyHtml .= '</td>';

                if (empty($columns) || count($columns) < 2) {
                    $priceCell = '-';
                    if (!empty($columns) && count($columns) === 1) {
                        $col = $columns[0];
                        foreach ($item['variants'] as $v) {
                            if ((int)$v['size_id'] === (int)$col['id']) {
                                $priceCell = 'R' . format_rand_no_decimals($v['price']);
                                break;
                            }
                        }
                    } elseif (!empty($item['variants'])) {
                        if (count($item['variants']) === 1) {
                            $priceCell = 'R' . format_rand_no_decimals($item['variants'][0]['price']);
                        } else {
                            $priceCell = 'R' . format_rand_no_decimals($item['min_price']) . '+';
                        }
                    }
                    $bodyHtml .= '<td class="price">' . h($priceCell) . '</td>';
                } else {
                    foreach ($columns as $col) {
                        $price = '-';
                        foreach ($item['variants'] as $v) {
                            if ((int)$v['size_id'] === (int)$col['id']) {
                                $price = 'R' . format_rand_no_decimals($v['price']);
                                break;
                            }
                        }
                        $bodyHtml .= '<td class="price">' . h($price) . '</td>';
                    }
                }

                $bodyHtml .= '</tr>';
            }

            $bodyHtml .= '</tbody></table>';
        }
    }

    $paperWidthPt = mm_to_points($trimWidthMm + (2 * $cropMarginMm));
    $paperHeightPt = mm_to_points($trimHeightMm + (2 * $cropMarginMm));

    $buildHtmlForScale = function($scale) use ($bodyHtml, $pageMarginTopMm, $pageMarginRightMm, $pageMarginBottomMm, $pageMarginLeftMm, $cropMarkLenMm, $baseMarginTopMm, $baseMarginRightMm, $baseMarginBottomMm, $baseMarginLeftMm) {
        $s = (float)$scale;
        if ($s <= 0) $s = 1.0;

        $bodyFontPx = 9.05 * $s;
        $logoWidthMm = 36.5 * $s;
        $headerMarginBottomMm = 1.3 * $s;
        $bizNamePx = 13 * $s;
        $bizLinePx = 9 * $s;
        $bizPhonePx = 9.5 * $s;
        $promoPx = 9.5 * $s;
        $bizLineMarginBottomMm = 0.4 * $s;
        $promoMarginTopMm = 0.45 * $s;
        $ruleMarginBottomMm = 0.85 * $s;

        $menuTableMarginBottomMm = 1.0 * $s;
        $theadFontPx = 8.6 * $s;
        $theadPadYmm = 0.65 * $s;
        $theadPadXmm = 0.9 * $s;
        $tdPadYmm = 0.45 * $s;
        $tdPadXmm = 0.9 * $s;
        $itemNamePx = 9.5 * $s;
        $itemDescPx = 8.0 * $s;
        $smallPx = 8.2 * $s;
        $specialsHeadingPx = 11 * $s;
        $specialsHeadingMarginBottomMm = 1.0 * $s;

        $css = '';
        $css .= '@page { margin: ' . format_css_number($pageMarginTopMm) . 'mm ' . format_css_number($pageMarginRightMm) . 'mm ' . format_css_number($pageMarginBottomMm) . 'mm ' . format_css_number($pageMarginLeftMm) . 'mm; }';
        $css .= 'body { font-family: Helvetica, sans-serif; font-size: ' . format_css_number($bodyFontPx) . 'px; color: #111; line-height: 1.12; }';

        $css .= '.header-table { width: 100%; border-collapse: collapse; margin-bottom: ' . format_css_number($headerMarginBottomMm) . 'mm; }';
        $css .= '.header-table td { padding: 0; vertical-align: top; }';
        $css .= '.logo { width: ' . format_css_number($logoWidthMm) . 'mm; height: auto; }';
        $css .= '.biz-name { font-size: ' . format_css_number($bizNamePx) . 'px; font-weight: bold; margin: 0 0 ' . format_css_number($bizLineMarginBottomMm) . 'mm 0; }';
        $css .= '.biz-line { font-size: ' . format_css_number($bizLinePx) . 'px; color: #333; margin: 0 0 ' . format_css_number($bizLineMarginBottomMm) . 'mm 0; }';
        $css .= '.biz-phone { font-size: ' . format_css_number($bizPhonePx) . 'px; font-weight: bold; color: #111; margin: 0; }';
        $css .= '.promo { font-size: ' . format_css_number($promoPx) . 'px; font-weight: bold; color: #b71c1c; margin: ' . format_css_number($promoMarginTopMm) . 'mm 0 0 0; text-transform: uppercase; }';
        $css .= '.rule { border-bottom: 0.6pt solid #ddd; margin: 0 0 ' . format_css_number($ruleMarginBottomMm) . 'mm 0; }';

        $css .= '.crop { position: fixed; width: ' . format_css_number($cropMarkLenMm) . 'mm; height: ' . format_css_number($cropMarkLenMm) . 'mm; }';
        $css .= '.crop.tl { top: -' . format_css_number($baseMarginTopMm + $cropMarkLenMm) . 'mm; left: -' . format_css_number($baseMarginLeftMm + $cropMarkLenMm) . 'mm; border-bottom: 0.35pt solid #000; border-right: 0.35pt solid #000; }';
        $css .= '.crop.tr { top: -' . format_css_number($baseMarginTopMm + $cropMarkLenMm) . 'mm; right: -' . format_css_number($baseMarginRightMm + $cropMarkLenMm) . 'mm; border-bottom: 0.35pt solid #000; border-left: 0.35pt solid #000; }';
        $css .= '.crop.bl { bottom: -' . format_css_number($baseMarginBottomMm + $cropMarkLenMm) . 'mm; left: -' . format_css_number($baseMarginLeftMm + $cropMarkLenMm) . 'mm; border-top: 0.35pt solid #000; border-right: 0.35pt solid #000; }';
        $css .= '.crop.br { bottom: -' . format_css_number($baseMarginBottomMm + $cropMarkLenMm) . 'mm; right: -' . format_css_number($baseMarginRightMm + $cropMarkLenMm) . 'mm; border-top: 0.35pt solid #000; border-left: 0.35pt solid #000; }';

        $css .= 'table { width: 100%; border-collapse: collapse; page-break-inside: auto; }';
        $css .= 'thead { display: table-header-group; }';
        $css .= 'tbody { display: table-row-group; }';
        $css .= '.menu-table { margin-bottom: ' . format_css_number($menuTableMarginBottomMm) . 'mm; }';
        $css .= 'thead th { background: #b71c1c; color: #fff; font-size: ' . format_css_number($theadFontPx) . 'px; font-weight: bold; padding: ' . format_css_number($theadPadYmm) . 'mm ' . format_css_number($theadPadXmm) . 'mm; border-bottom: 0; white-space: nowrap; }';
        $css .= 'thead th:first-child { box-shadow: -' . format_css_number($bleedXmm) . 'mm 0 0 ' . format_css_number($bleedXmm) . 'mm #b71c1c; }';
        $css .= 'thead th:last-child { box-shadow: ' . format_css_number($bleedXmm) . 'mm 0 0 ' . format_css_number($bleedXmm) . 'mm #b71c1c; }';
        $css .= 'th.left { text-align: left; }';
        $css .= 'th.category { color: #fff; text-transform: uppercase; letter-spacing: 0.2px; white-space: normal; }';
        $css .= '.days { color: #e0e0e0; font-weight: normal; text-transform: none; letter-spacing: 0; }';
        $css .= 'td { vertical-align: top; padding: ' . format_css_number($tdPadYmm) . 'mm ' . format_css_number($tdPadXmm) . 'mm; border-bottom: none; }';
        $css .= 'td.item { width: 62%; }';
        $css .= 'tr { page-break-inside: avoid; }';
        $css .= '.item-name { font-weight: bold; font-size: ' . format_css_number($itemNamePx) . 'px; margin: 0; line-height: 1.08; }';
        $css .= '.item-desc { color: #555; font-style: normal; font-size: ' . format_css_number($itemDescPx) . 'px; margin: 0; margin-top: 0; line-height: 0.98; }';
        $css .= '.price { font-weight: bold; text-align: center; white-space: nowrap; }';
        $css .= '.small { font-size: ' . format_css_number($smallPx) . 'px; color: #fff; font-weight: normal; }';
        $css .= '.muted { color: #666; }';
        $css .= '.specials-heading { font-size: ' . format_css_number($specialsHeadingPx) . 'px; font-weight: bold; margin-top: 0; margin-bottom: ' . format_css_number($specialsHeadingMarginBottomMm) . 'mm; color: #111; border-top: 0; padding-top: 0; }';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' . $css . '</style></head><body>' . $bodyHtml . '</body></html>';
    };

    $renderForScale = function($scale) use ($buildHtmlForScale, $paperWidthPt, $paperHeightPt) {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($buildHtmlForScale($scale));
        $dompdf->setPaper([0, 0, $paperWidthPt, $paperHeightPt]);
        $dompdf->render();

        $pageCount = 0;
        $canvas = $dompdf->getCanvas();
        if ($canvas) {
            $pageCount = (int)$canvas->get_page_count();
        }

        return [$pageCount, $dompdf->output()];
    };

    $targetPages = 2;
    $minScale = 0.35;
    $maxScale = 1.15;
    $maxIterations = 6;
    $renderAttempts = 0;

    $chosenScale = 1.0;
    $chosenPageCount = 0;
    $chosenPdfBytes = '';
    $autoScaled = true;

    list($pageCountDefault, $pdfDefault) = $renderForScale(1.0);
    $renderAttempts++;

    if ($pageCountDefault <= 0) {
        throw new Exception('PDF generator failed to determine page count.');
    }

    if ($pageCountDefault === $targetPages) {
        $chosenScale = 1.0;
        $chosenPageCount = $pageCountDefault;
        $chosenPdfBytes = $pdfDefault;
        $autoScaled = false;
    } elseif ($pageCountDefault > $targetPages) {
        list($pageCountMin, $pdfMin) = $renderForScale($minScale);
        $renderAttempts++;
        if ($pageCountMin > $targetPages) {
            $chosenScale = $minScale;
            $chosenPageCount = $pageCountMin;
            $chosenPdfBytes = $pdfMin;
        } else {
            $lo = $minScale;
            $hi = 1.0;
            $bestScale = $minScale;
            $bestPageCount = $pageCountMin;
            $bestPdf = $pdfMin;

            for ($i = 0; $i < $maxIterations; $i++) {
                $mid = ($lo + $hi) / 2;
                list($pc, $bytes) = $renderForScale($mid);
                $renderAttempts++;

                if ($pc <= 0) continue;

                if ($pc <= $targetPages) {
                    $lo = $mid;
                    $bestScale = $mid;
                    $bestPageCount = $pc;
                    $bestPdf = $bytes;
                } else {
                    $hi = $mid;
                }
            }

            $chosenScale = $bestScale;
            $chosenPageCount = $bestPageCount;
            $chosenPdfBytes = $bestPdf;
        }
    } else {
        list($pageCountMax, $pdfMax) = $renderForScale($maxScale);
        $renderAttempts++;
        if ($pageCountMax > 0 && $pageCountMax <= $targetPages) {
            $chosenScale = $maxScale;
            $chosenPageCount = $pageCountMax;
            $chosenPdfBytes = $pdfMax;
        } else {
            $lo = 1.0;
            $hi = $maxScale;
            $bestScale = 1.0;
            $bestPageCount = $pageCountDefault;
            $bestPdf = $pdfDefault;

            for ($i = 0; $i < $maxIterations; $i++) {
                $mid = ($lo + $hi) / 2;
                list($pc, $bytes) = $renderForScale($mid);
                $renderAttempts++;

                if ($pc <= 0) continue;

                if ($pc <= $targetPages) {
                    $lo = $mid;
                    $bestScale = $mid;
                    $bestPageCount = $pc;
                    $bestPdf = $bytes;
                } else {
                    $hi = $mid;
                }
            }

            $chosenScale = $bestScale;
            $chosenPageCount = $bestPageCount;
            $chosenPdfBytes = $bestPdf;
        }
    }

    $pdfBytes = $chosenPdfBytes;
    $outPath = __DIR__ . '/../assets/menu.pdf';
    $bytesWritten = @file_put_contents($outPath, $pdfBytes);
    if ($bytesWritten === false) {
        throw new Exception('Failed to write PDF to ' . $outPath . '. Check file permissions.');
    }

    if (ob_get_level() > 0 && ob_get_length() > 0) {
        $junk = (string)ob_get_clean();
        if (trim($junk) !== '') {
            error_log('generate_menu_pdf.php discarded output: ' . trim($junk));
        }
    }

    json_response([
        'success' => true,
        'bytes' => $bytesWritten,
        'path' => 'assets/menu.pdf',
        'page_count' => $chosenPageCount,
        'target_pages' => $targetPages,
        'scale' => (float)format_css_number($chosenScale),
        'auto_scaled' => $autoScaled,
        'render_attempts' => $renderAttempts,
        'generated_at' => date('c')
    ]);
} catch (Exception $e) {
    if (ob_get_level() > 0 && ob_get_length() > 0) {
        $junk = (string)ob_get_clean();
        if (trim($junk) !== '') {
            error_log('generate_menu_pdf.php discarded output (error path): ' . trim($junk));
        }
    }
    json_response(['error' => $e->getMessage()], 500);
}
