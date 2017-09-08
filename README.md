# cms_pico

### Installation:

After the git clone, install the composer dependencies:
>     composer install


### Apache configuration:

if mod_proxy and mod_proxy_http are running (safe enough ?)
>     ProxyPass /sites/  https://cloud.example.com/index.php/apps/cms_pico/pico/
>     ProxyPassReverse /sites/ https://cloud.example.com/index.php/apps/cms_pico/pico/

if no mod_proxy, use mod_rewrite:
>     RewriteEngine On
>     RewriteRule /sites/(.*) https://cloud.example.com/index.php/apps/cms_pico/pico/$1 [QSA,L]


If mod_proxy AND mod_rewrite (because why not! but useless):

>     RewriteEngine On
>     RewriteRule /sites/(.*) https://cloud.example.com/index.php/apps/cms_pico/pico/$1 [P]


