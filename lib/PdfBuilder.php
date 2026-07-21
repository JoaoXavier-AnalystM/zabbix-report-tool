<?php
// PdfBuilder (ASCII-safe) - build() static y embedding base64 (sin file://)

require_once __DIR__ . '/../config.php';

class PdfBuilder
{
    // entries: [ ['title'=>'...', 'png'=>'/abs/path.png'], ... ]
    public static function build(array $entries, string $outfile, string $userName = '', string $fromText = '', string $toText = '', $engine = 'dompdf'): void
    {
        if (empty($entries)) {
            throw new RuntimeException('Não há gráficos para gerar o PDF.');
        }

        $imgs = [];
        foreach ($entries as $i => $e) {
            $p = isset($e['png']) ? (string)$e['png'] : '';
            if ($p === '' || !is_file($p)) {
                throw new RuntimeException('PNG ausente para a entrada #'.$i);
            }
            $bin = @file_get_contents($p);
            if ($bin === false || strlen($bin) < 100) {
                throw new RuntimeException('PNG ilegível ou vazio em #'.$i);
            }
            $imgs[] = [
                'title' => (string)($e['title'] ?? ''),
                'b64'   => 'data:image/png;base64,'.base64_encode($bin),
            ];
        }

        $baseDir = __DIR__ . '/..';
        $zabbixLogo  = $baseDir . '/assets/Zabbix_logo.png';
        $unicredLogo = $baseDir . '/assets/unicred.svg';
        $zabbixB64   = is_file($zabbixLogo)  ? 'data:image/png;base64,'  . base64_encode(file_get_contents($zabbixLogo))  : '';
        $unicredB64  = is_file($unicredLogo) ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($unicredLogo)) : '';

        $html = self::buildHtml($imgs, $zabbixB64, $unicredB64, $userName, $fromText, $toText);

        $eng = $engine ?: 'dompdf';
        if ($eng === 'wkhtmltopdf') {
            self::buildWithWkhtml($html, $outfile);
        } else {
            self::buildWithDompdf($html, $outfile);
        }

        if (!is_file($outfile) || filesize($outfile) < 1000) {
            throw new RuntimeException('PDF vazio ou não gerado.');
        }
    }

    private static function buildHtml(array $imgs, string $zabbixLogo, string $unicredLogo, string $userName, string $fromText, string $toText): string
    {
        $blocks = [];
        $toc = [];
        $n = 1;

        foreach ($imgs as $img) {
            $id = 'g'.$n++;
            $t = htmlspecialchars($img['title'], ENT_QUOTES, 'UTF-8');
            $toc[] = ['id' => $id, 'title' => $t];
            $blocks[] = [
                'id' => $id,
                'title' => $t,
                'content' => '<div class="chart-block">'.
                           '<h2 id="'.$id.'" class="chart-title">'.$t.'</h2>'.
                           '<div class="chart-container">'.
                           '<img src="'.$img['b64'].'" class="chart-image" />'.
                           '</div></div>'
            ];
        }

        $tocHtml = '<div class="toc-container">'.
                  '<h2 class="toc-title">' . t('pdf_toc_title') . '</h2>'.
                  '<table class="toc-table"><tbody>';

        foreach ($toc as $entry) {
            $target = '#'.$entry['id'];
            $tocHtml .= '<tr class="toc-row">'
                       .'<td class="toc-title-cell"><a href="'.$target.'" class="toc-link">'.$entry['title'].'</a></td>'
                       .'<td class="toc-dots-cell"><span class="dots"></span></td>'
                       .'<td class="toc-page-cell"><span class="toc-page" data-target="'.$target.'"></span></td>'
                       .'</tr>';
        }
        $tocHtml .= '</tbody></table></div>';

        $content = '';
        foreach ($blocks as $block) {
            $content .= $block['content'];
        }

        $nowFormatted = date('d/m/Y H:i:s');
        $userDisplay = htmlspecialchars($userName ?: '—', ENT_QUOTES, 'UTF-8');
        $fromDisplay = $fromText ? htmlspecialchars($fromText, ENT_QUOTES, 'UTF-8') : '—';
        $toDisplay   = $toText   ? htmlspecialchars($toText,   ENT_QUOTES, 'UTF-8') : '—';
        $periodDisplay = $fromDisplay . '  →  ' . $toDisplay;

        $mainTitle = t('pdf_main_title');
        $generatedLabel = t('pdf_generated_on');
        $pageLabel = t('pdf_page_x_of_y');

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>'.$mainTitle.'</title>
            <style>
                @page { margin: 100px 45px 55px 45px; }
                body { font-family: Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.5; margin: 0; padding: 0; }

                /* ── HEADER ── */
                .header {
                    position: fixed; top: -80px; left: 0; right: 0; height: 50px;
                    padding: 6px 45px; display: flex; align-items: center; justify-content: space-between;
                    border-bottom: 2px solid #d00; background: #fff;
                }
                .header-left img { height: 24px; }
                .header-right img { height: 22px; width: auto; }

                /* ── FOOTER ── */
                .footer {
                    position: fixed; bottom: -38px; left: 0; right: 0; height: 22px;
                    font-size: 8px; color: #888;
                    border-top: 1px solid #ddd;
                    background: #fff; padding: 2px 45px;
                    display: flex; justify-content: space-between; align-items: center;
                }
                .footer img { height: 14px; }

                /* ── CONTENT ── */
                .cover-box {
                    border: 1px solid #ddd; border-radius: 6px; padding: 16px 20px;
                    margin-bottom: 24px; background: #fafafa;
                }
                .cover-box h1 { font-size: 16px; color: #1a1a2e; margin: 0 0 10px 0; }
                .cover-box .meta { font-size: 10px; color: #666; line-height: 1.8; }
                .cover-box .meta strong { color: #333; }

                .chart-block { margin: 0 0 16px 0; page-break-inside: avoid; padding: 8px 0 16px 0; border-bottom: 1px solid #f0f0f0; }
                .toc-container { margin-bottom: 28px; }
                .toc-title { color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 4px; margin-bottom: 12px; }
                .toc-table { width: 100%; border-collapse: collapse; table-layout: auto; }
                .toc-table td { padding: 3px 0; }
                .toc-row { height: auto; line-height: 1; vertical-align: middle; }
                .toc-title-cell { width: auto; padding: 2px 6px 2px 0; white-space: nowrap; word-break: keep-all; hyphens: none; overflow: hidden; text-overflow: ellipsis; }
                .toc-dots-cell { width: 100%; overflow: hidden; }
                .toc-dots-cell .dots { display: block; height: 0; margin: 0 4px; border-bottom: 1px dotted #9ab0be; }
                .toc-page-cell { width: 36px; text-align: right; padding-left: 4px; white-space: nowrap; }
                .toc-link { color: #2c3e50; text-decoration: none; display: inline-block; max-width: 100%; white-space: nowrap; word-break: keep-all; hyphens: none; overflow: hidden; text-overflow: ellipsis; }
                .toc-link:hover { color: #1a5276; text-decoration: underline; }
                .toc-page { color: #2c3e50; font-size: 0.9em; text-align: right; }
                .toc-page:after { content: target-counter(attr(data-target), page); }
                .chart-title { color: #1a5276; border-bottom: 1px solid #eee; padding-bottom: 4px; margin-bottom: 12px; font-size: 13px; }
                .chart-container { width: 100%; text-align: center; }
                .chart-image { max-width: 100%; height: auto; margin: 0 auto; display: block; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="header-left">
                    <img src="'.$zabbixLogo.'" alt="Zabbix">
                </div>
                <div class="header-right"><img src="'.$unicredLogo.'" alt="Unicred"></div>
            </div>

            <div class="content">
                <div class="cover-box">
                    <h1>'.$mainTitle.'</h1>
                    <div class="meta">
                        <strong>Usuário:</strong> '.$userDisplay.' &nbsp;|&nbsp;
                        <strong>Período:</strong> '.$fromDisplay.' até '.$toDisplay.' &nbsp;|&nbsp;
                        <strong>Gerado em:</strong> '.$nowFormatted.'
                    </div>
                </div>
                '.$tocHtml.'
                '.$content.'
            </div>

            <div class="footer">
                <span>'.$generatedLabel.' '.$nowFormatted.'</span>
                <img src="'.$zabbixLogo.'" alt="Zabbix">
            </div>
            <script type="text/php">
                if (isset($pdf)) {
                    $text = "'.$pageLabel.'";
                    $font = $fontMetrics->get_font("Arial, sans-serif", "normal");
                    $size = 7;
                    $w = $fontMetrics->get_text_width($text, $font, $size);
                    $y = $pdf->get_height() - 16;
                    $x = ($pdf->get_width() - $w) / 2;
                    $pdf->page_text($x, $y, $text, $font, $size, [0,0,0,0.4]);
                }
            </script>
        </body>
        </html>';

        return $html;
    }

    private static function buildWithDompdf(string $html, string $outfile): void
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Dompdf não está disponível. Instale com: composer require dompdf/dompdf:^1.2');
        }
        require_once $autoload;

        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);
        $dompdf = new Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($outfile, $dompdf->output());
    }

    private static function buildWithWkhtml(string $html, string $outfile): void
    {
        $tmp = (defined('APP_TMP') ? APP_TMP : sys_get_temp_dir()).DIRECTORY_SEPARATOR.'html_'.uniqid().'.html';
        file_put_contents($tmp, $html);
        $cmd = 'wkhtmltopdf --enable-local-file-access --quiet --margin-top 70 --margin-bottom 40 --header-html "about:blank" --footer-html "about:blank" '.escapeshellarg($tmp).' '.escapeshellarg($outfile).' 2>&1';
        exec($cmd, $out, $rc);
        @unlink($tmp);
        if ($rc !== 0) {
            throw new RuntimeException('wkhtmltopdf falhou rc='.$rc);
        }
    }
}