# توسعه‌ی افزونه

مبنای توسعه‌ی افزونه‌ی چابک بر دو چیز است: یکی سرعت و دومی قابل‌توسعه‌بودن.

شما می‌توانید افزونه‌ی چابک را به راحتی توسعه دهید، رفتار آن را تغییر دهید و یا از امکانات آن برای پیشبرد اهدافتان استفاده کنید. به یاد داشته باشید که افزونه‌ی چابک تحت لایسنس GPL نسخه‌ی ۲ عرضه می‌شود.

## گلوبال‌ها
* `$chabok_options`: از طریق آن می‌توانید به تنظیمات چابک دسترسی پیدا کنید.
* `chabok_io()->api`: کلاس کلاینت REST API چابک را می‌توانید استفاده کنید.

## فیلترها
افزونه‌ی چابک فیلترهای مختلفی را در دسترس شما قرار می‌دهد.

### فیلترهای مربوط به صفحه‌ی تنظیمات
* `chabok_user_id_keys`: کلیدهای شناسایی کاربر رو می‌توانید تغییر دهید. دقت کنید که مقدار مربوط به کلید باید برای هر کاربر با تابع `WP_User::get()` دردسترس باشد و حتماً یک مقدار عددی یا رشته باشد.
* `chabok_registered_options`: با این فیلتر می‌توانید همه‌ی فیلدها و تب‌های تنظیمات افزونه را کنترل کنید.
* `chabok_core_options`: فیلدهای تب پارامترها را کنترل کنید.
* `chabok_attribution_options`: فیلدهای تب اتریبیوشن را کنترل کنید.
* `chabok_tracking_options`: فیلدهای تب ترکینگ را کنترل کنید.
* `chabok_advanced_options`: فیلدهای تب پیشرفته را کنترل کنید.
* `chabok_get_options`: مقدار تنظیمات را قبل از این که تحویل متغیر `$chabok_options` شوند را کنترل کنید.

### ترکینگ
هر کدام از فیلترهای زیر، پارامترهای دیگری را هم به شما تحویل می‌دهند که می‌توانید در فایل tracking.php آن‌ها را ببینید.

* `chabok_on_comment_data`: داده‌ای که همراه رویداد `comment` ارسال می‌شود را می‌توانید کنترل کنید.
* `chabok_on_search_data`: داده‌ای که همراه رویداد `search` ارسال می‌شود را می‌توانید کنترل کنید.
* `chabok_on_single_data`: داده‌ای که همراه رویداد `view_post` ارسال می‌شود را می‌توانید کنترل کنید.

## نمونه‌ی ترک‌کردن افزونه‌ی 3rd party با چابک
در این مثال، با استفاده از چابک، رفتار add to cart افزونه‌ی Easy Digital Downloads را پیگیری می‌کنیم.
در این افزونه، ابتدا تنظیمات مربوط به آن به قسمت ترکینگ افزونه افزوده می‌شود، سپس در اکشن مربوط به خود Easy Digital Downloads، رویداد را برای چابک ارسال می‌کنیم:

```php
<?php
/**
 * Plugin Name: EDD Integration for Chabok.IO
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'chabok_io' ) ) {
  return;
}

if ( ! function_exists( 'EDD' ) ) {
  return;
}

add_filter( 'chabok_tracking_options', function( $settings ) {
	return array_merge( $settings, array(
		'track_edd_add_to_cart'   => array(
			'id'                    => 'track_edd_add_to_cart',
			'name'                 	=> 'EDD: ثبت اضافه به سبد خرید',
			'type'					=> 'radio',
			'options'				=> array(
				'on'				=> __( 'On', 'chabok-io' ),
				'off'				=> __( 'Off', 'chabok-io' ),
			),
			'std'					=> 'off',
			'desc'					=> 'اگر این گزینه فعال باشد، هنگامی که کاربری موردی به سبد خرید خودش با افزونه‌ی EDD اضافه می‌کند، رفتار او در چابک ثبت می‌شود.',
		),
  	) );
} );

add_filter( 'edd_add_to_cart_item', function( $item ) {
	$item_id = $item['id'];

	ch_start_session();

	$installation_id = $_SESSION['chabok_device_id'];
	$user_id = chabok_get_user();
	if (! $user_id) {
		if ( isset( $_SESSION['chabok_user_id'] ) ) {
			$user_id = $_SESSION['chabok_user_id'];
		}
	}
	chabok_io()->api->track_event(
		'add_to_cart',
		$user_id,
		$installation_id,
		array(
			'item_id'		=> $item_id,
		)
	);

	return $item;
} );
```