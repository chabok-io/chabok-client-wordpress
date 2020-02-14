# تنظیمات بازنویسی فایل Worker

برای دریافت پوش نوتیفیکیشن در وب‌سایت، باید فایل ChabokSDKWorker.js را در صفحات وب‌سایت‌تان فراخوانی کنید. افزونه‌ی «هماهنگی با چابک» این کار را برای شما انجام می‌دهد.

به دلیل محدودیتی که مرورگرها در نظر گرفته‌اند، فایل ChabokSDKWorker.js باید در مسیر ریشه‌ (روت) وب‌سایت شما در دسترس باشد.

یعنی به این صورت قابل‌قبول نیست:
```
https://example.com/wp-content/plugins/chabok-io/assets/js/ChabokSDKWorker.js
```
و باید به این صورت باشد:
```
https://example.com/ChabokSDKWorker.js
```

اما از آن‌جایی که فایل ورکر واقعاً در ریشه وجود ندارد، مجبور به استفاده از تکنیک بازنویسی (Rewrite) هستیم. افزونه‌ی چابک، به طور پیشفرض این فایل را در ریشه‌ی وب‌سایت‌تان بازنویسی می‌کند، اما به دلیل محدودیت‌هایی که توسط وردپرس و وب‌سرورها تعیین می‌شود، نمی‌تواند در بعضی موارد خاص مثل ساب‌دامین‌ها پاسخگو باشد، به همین خاطر شما باید از لایه‌ی وب‌سرور عمل بازنویسی را انجام دهید.

> **دقت کنید**: فایل ChabokSDKWorker را مستقیماً در ریشه‌ی وب‌سایت خودتان کپی نکنید! چرا که این فایل معمولاً با هر بار به‌روز‌رسانی افزونه‌ی چابک، به‌روز می‌شود و افزونه نمی‌تواند فایل خارج از دایرکتوری خودش را تشخیص بدهد و به‌روز کند.

در ادامه، چگونگی انجام بازنویسی را با وب‌سرورهای متداول توضیح داده شده است.

> **نکته**: قبل از فعال‌سازی پیکربندی جدید وب‌سرورتان، گزینه‌ی «بازنویسی ServiceWorker» را در قسمت پیشرفته در تنظیمات افزونه‌ی چابک وردپرس‌تان غیرفعال کنید.

## با htaccess
اکثر هوست‌های اشتراکی از فایل htaccess پشتیبانی می‌کنند. اگر شما از هوست‌های اشتراکی یا وب‌سرور apache2 استفاده می‌کنید، با افزودن خط‌های زیر به فایل htaccess واقع در ریشه‌ی وب‌سایت‌تان می‌توانید به هدف برسید:
```
RewriteRule ChabokSDKWorker.js wp-content/plugins/chabok-io/assets/js/ChabokSDKWorker.js [L]
RewriteRule ChabokSDKWorker.js.map wp-content/plugins/chabok-io/assets/js/ChabokSDKWorker.js.map [L]
```
به یاد داشته باشید که اگر نام دایرکتوری‌ای که در آن افزونه‌ی چابک را نصب کرده‌اید، غیر از `wp-content/plugins/chabok-io/` است، باید در فایل htaccess خود تغییر دهید.

## با nginx

اگر از وب‌سرور nginx استفاده می‌کنید، باید در قسمت تنظیمات server block مربوط به وردپرس، این خط‌ها را استفاده کنید:
```
rewrite ^/ChabokSDKWorker\.js$ /wp-content/plugins/chabok-io/assets/js/ChabokSDKWorker.js last;
rewrite ^/ChabokSDKWorker\.js\.map$ /wp-content/plugins/chabok-io/assets/js/ChabokSDKWorker.js.map last;
```
شما می‌توانید این دو خط را در بلاک server یا location قرار دهید. به یاد داشته باشید که اگر نام دایرکتوری‌ای که در آن افزونه‌ی چابک را نصب کرده‌اید، غیر از `wp-content/plugins/chabok-io/` است، باید در فایل خود تغییر دهید.