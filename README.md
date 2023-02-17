# wpsync-webspark
Installation:
1. Install WordPress on your server
2. Install WooCommerce plugin (Use any theme compatible with it for example: twentytwentythree)
3. Copy wpsync-webspark directory to wp-content/plugins or add this directory to zip archive and then upload it via wordpress admin panel (Plugins->Add new->Upload plugin)

IMPORTANT: make sure that cron is configured on the server otherwise the plugin will not work!

How it works:
After plugin activation cron task is created that is scheduled to run in 2 minutes. The first creation process will be long enough for additional cron jobs to be created (due to the addition of pictures, unfortunately I did not find a faster way to perform this operation). Therefore, I repeat that if the cron on the server does not work, then nothing will happen.
