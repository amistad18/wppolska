<?php
/**
 * Podstawowa konfiguracja WordPressa.
 *
 * Ten plik zawiera konfiguracje: ustawień MySQL-a, prefiksu tabel
 * w bazie danych, tajnych kluczy, używanej lokalizacji WordPressa
 * i ABSPATH. Więćej informacji znajduje się na stronie
 * {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Kodeksu. Ustawienia MySQL-a możesz zdobyć
 * od administratora Twojego serwera.
 *
 * Ten plik jest używany przez skrypt automatycznie tworzący plik
 * wp-config.php podczas instalacji. Nie musisz korzystać z tego
 * skryptu, możesz po prostu skopiować ten plik, nazwać go
 * "wp-config.php" i wprowadzić do niego odpowiednie wartości.
 *
 * @package WordPress
 */

// ** Ustawienia MySQL-a - możesz uzyskać je od administratora Twojego serwera ** //
/** Nazwa bazy danych, której używać ma WordPress */
define('DB_NAME', 'wordpress');

/** Nazwa użytkownika bazy danych MySQL */
define('DB_USER', 'root');

/** Hasło użytkownika bazy danych MySQL */
define('DB_PASSWORD', '');

/** Nazwa hosta serwera MySQL */
define('DB_HOST', 'localhost');

/** Kodowanie bazy danych używane do stworzenia tabel w bazie danych. */
define('DB_CHARSET', 'utf8');

/** Typ porównań w bazie danych. Nie zmieniaj tego ustawienia, jeśli masz jakieś wątpliwości. */
define('DB_COLLATE', '');

/**#@+
 * Unikatowe klucze uwierzytelniania i sole.
 *
 * Zmień każdy klucz tak, aby był inną, unikatową frazą!
 * Możesz wygenerować klucze przy pomocy {@link https://api.wordpress.org/secret-key/1.1/salt/ serwisu generującego tajne klucze witryny WordPress.org}
 * Klucze te mogą zostać zmienione w dowolnej chwili, aby uczynić nieważnymi wszelkie istniejące ciasteczka. Uczynienie tego zmusi wszystkich użytkowników do ponownego zalogowania się.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'k&n[lxY DK3O/-A~J2R+n0X@n1%&ktbb{9(D#IkMQLowVxbEWI5aMy%.^}Zza$zM');
define('SECURE_AUTH_KEY',  'sNAE-yTQ?FpY3h<1t#U0m)Ha?pJ|dV5u$,3|+.f3(5{UW4 8+awBrMYERhe}]IQ>');
define('LOGGED_IN_KEY',    'Fp+IAywE*P-?jSP-O &mRGJ/`Xc31(@M,_>HnK(adS@IhQ`(&-Zg]EJU^>-S$YCr');
define('NONCE_KEY',        '<uLgt{* [A5PJl_NIM?_35gGmI !|Wg7q[}KHe{%|]9[C~=#pQa|wZw?sy}=O{]k');
define('AUTH_SALT',        '#*$Ry!MgL;xu,^W:g{l!m48{pRx$ElF-1!QjUSvl.a>4-xN~Gw|2WpWLZ6O^^_km');
define('SECURE_AUTH_SALT', 'Dz;(Mtt4bTECSE)HPz4w{sG-f8BK![|RxQ/sKUb-%Z@$1U4]WTw84&y,`LfS-3Cf');
define('LOGGED_IN_SALT',   'TfcP]=;.3b|}!JIEB}8-10oz@5(1K6|7T#! =)LGLSs|WO&g4awOf|{a}b{4s|+w');
define('NONCE_SALT',       '8R7w(YvM%h|Vf-2dxIAy8zxl}-n;5y8fJT)lS|&|aIm1#`eg`9x.Wh^JX,mJ3U25');

/**#@-*/

/**
 * Prefiks tabel WordPressa w bazie danych.
 *
 * Możesz posiadać kilka instalacji WordPressa w jednej bazie danych,
 * jeżeli nadasz każdej z nich unikalny prefiks.
 * Tylko cyfry, litery i znaki podkreślenia, proszę!
 */
$table_prefix  = 'wp_';

/**
 * Kod lokalizacji WordPressa, domyślnie: angielska.
 *
 * Zmień to ustawienie, aby włączyć tłumaczenie WordPressa.
 * Odpowiedni plik MO z tłumaczeniem na wybrany język musi
 * zostać zainstalowany do katalogu wp-content/languages.
 * Na przykład: zainstaluj plik de_DE.mo do katalogu
 * wp-content/languages i ustaw WPLANG na 'de_DE', aby aktywować
 * obsługę języka niemieckiego.
 */
define('WPLANG', 'pl_PL');

/**
 * Dla programistów: tryb debugowania WordPressa.
 *
 * Zmień wartość tej stałej na true, aby włączyć wyświetlanie ostrzeżeń
 * podczas modyfikowania kodu WordPressa.
 * Wielce zalecane jest, aby twórcy wtyczek oraz motywów używali
 * WP_DEBUG w miejscach pracy nad nimi.
 */
define('WP_DEBUG', false);

/* To wszystko, zakończ edycję w tym miejscu! Miłego blogowania! */

/** Absolutna ścieżka do katalogu WordPressa. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Ustawia zmienne WordPressa i dołączane pliki. */
require_once(ABSPATH . 'wp-settings.php');
