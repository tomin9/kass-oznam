<?php
/**
 * Plugin Name: Kass Oznam – upozornenie pred nákupom vstupeniek
 * Description: Medzistránka s upozornením (napr. presun podujatia) pred presmerovaním na externý predaj vstupeniek. Jedna stránka pre všetky podujatia.
 * Version: 1.0.2
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

/** Predvolené hodnoty. */
function kass_oznam_defaults() {
	return array(
		KASS_OZNAM_OPT_NOTICE => "Upozornenie: podujatie bolo presunuté do Kina Baník.\nSkontrolujte si prosím miesto konania pred zakúpením vstupeniek.",
		KASS_OZNAM_OPT_BUTTON => 'Rozumiem, pokračovať na nákup vstupeniek',
	);
}

function kass_oznam_get( $key ) {
	$d = kass_oznam_defaults();
	return get_option( $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
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
		echo '<div class="kass-oznam__notice">' . wpautop( esc_html( kass_oznam_get( KASS_OZNAM_OPT_NOTICE ) ) ) . '</div>';
		echo '<p><a class="kass-oznam__button" href="' . esc_url( $e['url'] ) . '" rel="noopener">' . esc_html( kass_oznam_get( KASS_OZNAM_OPT_BUTTON ) ) . '</a></p>';
	} else {
		echo '<div class="kass-oznam__notice">' . wpautop( esc_html( kass_oznam_get( KASS_OZNAM_OPT_NOTICE ) ) ) . '</div>';
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
	$css = '.kass-oznam__notice{background:#fff4e5;border-left:5px solid #e67e00;padding:1em 1.25em;margin:1em 0;font-size:1.1em}'
		. '.kass-oznam__notice,.kass-oznam__notice p{color:#1a1a1a!important}.kass-oznam__notice p{margin:0 0 .5em}.kass-oznam__notice p:last-child{margin-bottom:0}'
		. '.kass-oznam__button{display:inline-block;background:#c0392b;color:#fff!important;padding:.8em 1.6em;border-radius:4px;text-decoration:none;font-weight:bold}'
		. '.kass-oznam__button:hover{background:#962d22}';
	// Stránka s medzistránkou vyplní celú výšku okna (pätička na spodku, bez bieleho pásu).
	$css .= 'body.kass-oznam-page{min-height:100vh;background:#1d1f27}'
		. 'body.kass-oznam-page #wrapper{min-height:100vh;display:flex;flex-direction:column}'
		. 'body.kass-oznam-page #wrapper>#main{flex:1 0 auto}'
		. 'body.kass-oznam-page #side-header{min-height:100vh}'
		. 'body.kass-oznam-page #side-header .side-header-wrapper{min-height:100vh}';
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
					<th><label for="n">Text upozornenia</label></th>
					<td><textarea id="n" name="<?php echo esc_attr( KASS_OZNAM_OPT_NOTICE ); ?>" rows="4" class="large-text"><?php echo esc_textarea( kass_oznam_get( KASS_OZNAM_OPT_NOTICE ) ); ?></textarea>
					<p class="description">Zobrazí sa pri všetkých podujatiach. Pri ďalšej zmene stačí upraviť tu.</p></td>
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
