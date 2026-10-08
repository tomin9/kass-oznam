<?php
/**
 * Plugin Name: Kass Oznam – upozornenie pred nákupom vstupeniek
 * Description: Medzistránka s upozornením (napr. presun podujatia) pred presmerovaním na externý predaj vstupeniek. Jedna stránka pre všetky podujatia.
 * Version: 1.2.1
 * Author: Ars Preuge
 * Text Domain: kass-oznam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KASS_OZNAM_OPT_NOTICE  = 'kass_oznam_notice';
const KASS_OZNAM_OPT_EVENTS  = 'kass_oznam_events';
const KASS_OZNAM_OPT_PAGE    = 'kass_oznam_page_url';
const KASS_OZNAM_OPT_BUTTON  = 'kass_oznam_button';
const KASS_OZNAM_OPT_HEAD    = 'kass_oznam_headline';

/** Predvolené hodnoty. */
function kass_oznam_defaults() {
	return array(
		KASS_OZNAM_OPT_NOTICE => "Vážení návštevníci a priaznivci kultúry,\noznamujeme Vám, že vďaka podpore mesta Prievidza sme získali nenávratné finančné prostriedky na modernizáciu divadelnej sály v Dome kultúry. Projekt je zameraný na obnovu javiskovej techniky, ozvučenia, osvetlenia a súvisiacich technických systémov, ako aj výmenu súčasného zasúvacieho hľadiska za nové vrátane nových, pohodlnejších sedadiel.\nRekonštrukčné práce sa začali v týchto dňoch a hoci sa tešíme na nové možnosti, ktoré nám modernizácia prinesie, rozsah a charakter prác nám neumožňuje využívať sálu v štandardnom prevádzkovom režime.\nPredpokladaný termín ukončenia projektu je do konca januára 2027.\n\nPlánované podujatia, divadelné predstavenia a koncerty, ktoré je technicky a organizačne možné zvládnuť v inom priestore, preto **presúvame do kinosály Kina Baník, Ul. M.R. Štefánika 1.**\n\nVeríme, že po dokončení budeme môcť našim divákom a návštevníkom ponúknuť ešte kvalitnejšie služby, lepší zážitok a moderné prostredie pre kultúru.\n\nĎakujeme Vám za pochopenie a trpezlivosť.\n\nAktuálne informácie o termínoch a miestach konania podujatí nájdete na našej web stránke [www.kasspd.sk](https://www.kasspd.sk)",
		KASS_OZNAM_OPT_HEAD   => 'Podujatie je presunuté do Kina Baník',
		KASS_OZNAM_OPT_BUTTON => 'Rozumiem, pokračovať na nákup vstupeniek',
	);
}

function kass_oznam_get( $key ) {
	$d = kass_oznam_defaults();
	return get_option( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}


/** Text upozornenia: bezpečné escapovanie + **zvýraznenie** + [odkazy](https://...) + odseky. */
function kass_oznam_format( $text ) {
	$html = esc_html( $text );
	$html = preg_replace( '/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $html );
	$html = preg_replace_callback(
		'/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u',
		function ( $m ) {
			return '<a href="' . esc_url( wp_specialchars_decode( $m[2] ) ) . '" rel="noopener">' . $m[1] . '</a>';
		},
		$html
	);
	return wpautop( $html );
}

/** Blok: výrazný nadpis + text upozornenia. */
function kass_oznam_notice_html() {
	$out = '';
	$head = trim( (string) kass_oznam_get( KASS_OZNAM_OPT_HEAD ) );
	if ( '' !== $head ) {
		$out .= '<div class="kass-oznam__headline"><span aria-hidden="true">&#9888;</span> ' . esc_html( $head ) . '</div>';
	}
	return $out . '<div class="kass-oznam__notice">' . kass_oznam_format( kass_oznam_get( KASS_OZNAM_OPT_NOTICE ) ) . '</div>';
}

/** Zoznam podujatí: riadky v tvare "slug | Názov | https://odkaz-na-predaj". */
function kass_oznam_events() {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) get_option( KASS_OZNAM_OPT_EVENTS, '' ) ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 3 ) );
		if ( count( $parts ) < 3 || '' === $parts[0] ) {
			continue;
		}
		$url = esc_url_raw( $parts[2], array( 'http', 'https' ) );
		if ( $url ) {
			$out[ sanitize_title( $parts[0] ) ] = array( 'name' => $parts[1], 'url' => $url );
		}
	}
	return $out;
}

/** Shortcode [kass_vstupenky] – vložte ho na jednu stránku (napr. /vstupenky). */
add_shortcode( 'kass_vstupenky', function () {
	$slug   = isset( $_GET['podujatie'] ) ? sanitize_title( wp_unslash( $_GET['podujatie'] ) ) : '';
	$events = kass_oznam_events();

	ob_start();
	echo '<div class="kass-oznam">';
	if ( $slug && isset( $events[ $slug ] ) ) {
		$e = $events[ $slug ];
		echo '<h2>' . esc_html( $e['name'] ) . '</h2>';
		echo kass_oznam_notice_html();
		echo '<p><a class="kass-oznam__button" href="' . esc_url( $e['url'] ) . '" rel="noopener">' . esc_html( kass_oznam_get( KASS_OZNAM_OPT_BUTTON ) ) . '</a></p>';
	} else {
		echo kass_oznam_notice_html();
		if ( $events ) {
			echo '<ul class="kass-oznam__list">';
			foreach ( $events as $s => $e ) {
				echo '<li><a href="' . esc_url( add_query_arg( 'podujatie', $s ) ) . '">' . esc_html( $e['name'] ) . '</a></li>';
			}
			echo '</ul>';
		}
	}
	echo '</div>';
	return ob_get_clean();
} );

/** Pomocná trieda na <body> pre stránku s shortcodom. */
add_filter( 'body_class', function ( $classes ) {
	if ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_the_ID() ), 'kass_vstupenky' ) ) {
		$classes[] = 'kass-oznam-page';
	}
	return $classes;
} );

add_action( 'wp_enqueue_scripts', function () {
	$css = '.kass-oznam__headline{background:#c0392b;color:#fff!important;font-size:1.6em;font-weight:800;text-transform:uppercase;letter-spacing:.02em;line-height:1.25;padding:.7em 1em;margin:1em 0 0;border-radius:4px 4px 0 0}'
		. '.kass-oznam__notice{background:#2c303c;border-left:5px solid #f39c12;padding:1.1em 1.4em;margin:0 0 1.2em;font-size:1.1em;border-radius:0 0 4px 4px}'
		. '.kass-oznam__notice,.kass-oznam__notice p{color:#eceff4!important}.kass-oznam__notice p{margin:0 0 .8em}.kass-oznam__notice p:last-child{margin-bottom:0}'
		. '.kass-oznam__notice a{color:#ffc15e!important;text-decoration:underline}.kass-oznam__notice strong{color:#ffc15e!important;font-weight:800}'
		. '.kass-oznam__button{display:inline-block;background:#c0392b;color:#fff!important;padding:.9em 1.8em;border-radius:4px;text-decoration:none;font-weight:bold;font-size:1.1em}'
		. '.kass-oznam__button:hover{background:#962d22}';
	// Responzivita: nadpis a text sa zalamujú, tlačidlo je na mobile cez celú šírku.
	$css .= '.kass-oznam{box-sizing:border-box;max-width:100%;overflow-wrap:anywhere}'
		. '.kass-oznam *{box-sizing:border-box;max-width:100%}'
		. '.kass-oznam__headline{overflow-wrap:break-word;hyphens:auto}'
		. '@media (max-width:1024px){.kass-oznam__headline{font-size:1.35em}.kass-oznam__notice{font-size:1.05em}}'
		. '@media (max-width:600px){.kass-oznam h2{font-size:1.4em;line-height:1.25}'
		. '.kass-oznam__headline{font-size:1.1em;padding:.7em .9em}'
		. '.kass-oznam__notice{font-size:1em;padding:.9em 1em;border-left-width:4px}'
		. '.kass-oznam__button{display:block;width:100%;text-align:center;padding:1em .8em}}';
	// Len na veľkých obrazovkách (bočné menu Avada): stránka vyplní celú výšku okna.
	// Na tablete a telefóne sa menu mení na horný pruh, tam výšku nenaťahujeme.
	$css .= 'body.kass-oznam-page{background:#1d1f27}'
		. '@media (min-width:1025px){'
		. 'body.kass-oznam-page{min-height:100vh}'
		. 'body.kass-oznam-page #wrapper{min-height:100vh;display:flex;flex-direction:column}'
		. 'body.kass-oznam-page #wrapper>#main{flex:1 0 auto}'
		. 'body.kass-oznam-page #side-header,body.kass-oznam-page #side-header .side-header-wrapper{min-height:100vh}}';
	wp_register_style( 'kass-oznam', false );
	wp_enqueue_style( 'kass-oznam' );
	wp_add_inline_style( 'kass-oznam', $css );
} );

/** Medzistránku nechceme v indexe vyhľadávačov. */
add_action( 'wp_head', function () {
	if ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_the_ID() ), 'kass_vstupenky' ) ) {
		echo '<meta name="robots" content="noindex,follow">' . "\n";
	}
} );

/** Admin: Nastavenia → Kass Oznam. */
add_action( 'admin_menu', function () {
	add_options_page( 'Kass Oznam', 'Kass Oznam', 'manage_options', 'kass-oznam', 'kass_oznam_settings_page' );
} );

add_action( 'admin_init', function () {
	register_setting( 'kass_oznam', KASS_OZNAM_OPT_NOTICE, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'kass_oznam', KASS_OZNAM_OPT_HEAD, array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'kass_oznam', KASS_OZNAM_OPT_BUTTON, array( 'sanitize_callback' => 'sanitize_text_field' ) );
	register_setting( 'kass_oznam', KASS_OZNAM_OPT_EVENTS, array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
	register_setting( 'kass_oznam', KASS_OZNAM_OPT_PAGE, array( 'sanitize_callback' => 'esc_url_raw' ) );
} );

function kass_oznam_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$page = kass_oznam_get( KASS_OZNAM_OPT_PAGE );
	?>
	<div class="wrap">
		<h1>Kass Oznam – upozornenie pred nákupom vstupeniek</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'kass_oznam' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="h">Výrazný nadpis</label></th>
					<td><input id="h" type="text" class="large-text" name="<?php echo esc_attr( KASS_OZNAM_OPT_HEAD ); ?>" value="<?php echo esc_attr( kass_oznam_get( KASS_OZNAM_OPT_HEAD ) ); ?>">
					<p class="description">Zobrazí sa veľkými písmenami v červenom pruhu nad textom. Nechajte prázdne, ak ho nechcete.</p></td>
				</tr>
				<tr>
					<th><label for="n">Text upozornenia</label></th>
					<td><textarea id="n" name="<?php echo esc_attr( KASS_OZNAM_OPT_NOTICE ); ?>" rows="4" class="large-text"><?php echo esc_textarea( kass_oznam_get( KASS_OZNAM_OPT_NOTICE ) ); ?></textarea>
					<p class="description">Zobrazí sa pri všetkých podujatiach. Dôležité časti zvýraznite dvojitými hviezdičkami: <code>**presúvame do Kina Baník**</code>. Odkaz: <code>[text](https://adresa)</code>. Nový odsek = prázdny riadok.</p></td>
				</tr>
				<tr>
					<th><label for="b">Text tlačidla</label></th>
					<td><input id="b" type="text" class="regular-text" name="<?php echo esc_attr( KASS_OZNAM_OPT_BUTTON ); ?>" value="<?php echo esc_attr( kass_oznam_get( KASS_OZNAM_OPT_BUTTON ) ); ?>"></td>
				</tr>
				<tr>
					<th><label for="p">Adresa medzistránky</label></th>
					<td><input id="p" type="url" class="regular-text" name="<?php echo esc_attr( KASS_OZNAM_OPT_PAGE ); ?>" value="<?php echo esc_attr( $page ); ?>" placeholder="https://vasastranka.sk/vstupenky/">
					<p class="description">Stránka, na ktorej je vložený shortcode <code>[kass_vstupenky]</code>. Slúži na vygenerovanie odkazov nižšie.</p></td>
				</tr>
				<tr>
					<th><label for="e">Podujatia</label></th>
					<td><textarea id="e" name="<?php echo esc_attr( KASS_OZNAM_OPT_EVENTS ); ?>" rows="10" class="large-text code" placeholder="koncert-jar | Jarný koncert | https://predaj.example.sk/podujatie/123"><?php echo esc_textarea( get_option( KASS_OZNAM_OPT_EVENTS, '' ) ); ?></textarea>
					<p class="description">Jeden riadok = jedno podujatie: <code>kod | Názov | odkaz na predaj</code>. Kód: malé písmená, čísla a pomlčky.</p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
		<?php if ( $page && kass_oznam_events() ) : ?>
			<h2>Odkazy, ktoré dajte na stránky podujatí</h2>
			<table class="widefat striped" style="max-width:900px"><tbody>
			<?php foreach ( kass_oznam_events() as $s => $e ) : ?>
				<tr><td><?php echo esc_html( $e['name'] ); ?></td><td><code><?php echo esc_html( add_query_arg( 'podujatie', $s, $page ) ); ?></code></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php endif; ?>
	</div>
	<?php
}
