<?php
/**
 * Server-side render elementu „Mapa okolí“ (MartinDV\MapaOkoli).
 * Element Studio tento soubor neupravuje – mění se jen tady v pluginu.
 *
 * Content → mapa → projekt_id, mapa (pořadí mapy v projektu, od 1), seznam_vlevo, seznam_pod, dva_sloupce, bez_ztlumeni
 * Vzhled z Design tabu řeší css.twig (CSS proměnné --mdo-*).
 *
 * @var array $propertiesData
 */

if (!function_exists('\MartinDV\render_okoli')) {
    echo '<p>Plugin „Marmal – Developerské projekty“ není aktivní.</p>';
    return;
}

$martin_dv_okoli_c = $propertiesData['content']['mapa'] ?? [];
if (!empty($martin_dv_okoli_c['seznam_pod'])) {
    $martin_dv_okoli_layout = 'below';
} elseif (!empty($martin_dv_okoli_c['seznam_vlevo'])) {
    $martin_dv_okoli_layout = 'left';
} else {
    $martin_dv_okoli_layout = 'right';
}

$martin_dv_okoli_html = \MartinDV\render_okoli(
    absint($martin_dv_okoli_c['projekt_id'] ?? 0),
    [
        'map'    => max(1, absint($martin_dv_okoli_c['mapa'] ?? 1)),
        'layout' => $martin_dv_okoli_layout,
        'cols'   => !empty($martin_dv_okoli_c['dva_sloupce']) ? 2 : 1,
        'dim'    => empty($martin_dv_okoli_c['bez_ztlumeni']),
    ]
);
// render_okoli() vrací hotové HTML, ve kterém je každá hodnota escapovaná (esc_html/esc_attr/esc_url), SVG ikony jsou z pevné sady v kódu.
echo $martin_dv_okoli_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
