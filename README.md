# Usage

```php
// File functions.

function sxgeo_api( $ip ) {

	$ip = $_SERVER[ 'REMOTE_ADDR' ];

	return json_decode( file_get_contents( 'http://ru.sxgeo.city/json/' . $ip . '/' ), true );
}

function client_ip_manager_settings() {

	ar_client_ip_manager()->settings( array( 'set_user_func' => 'sxgeo_api' ) );

}
add_action( 'init', 'client_ip_manager_settings' );

```

```php
// Anywhere

$geo = ar_client_ip_manager()->get_user();

```
