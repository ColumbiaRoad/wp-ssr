# WordPress SSR

> Poor man's Server Side Rendering

This application aims to solve server side rendering of JavaScript applications inside WordPress templates. It runs in two parts. First there is the WordPress plugin which creates new post type for the renders and REST API endpoints to get an array of render objects. Secondly there is Express server which when accessed fetches these render objects and launches puppeteer to render the pages. When the page is rendered and the wanted application part of that page is parsed, it will save the html back to the post through the REST API.

## Disclaimer

This application doesn't provide you real time server side rendering but it's suitable for better SEO accessibility for your content. The puppeteer renders the page always as unauthenticated user and it requires that the state changes are reflected in the url.

## Setup

### WordPress

You can install the plugin using composer:

```bash
composer require "columbiaroad/wp-ssr:0.1.2@dev"
```

First you need to navigate to the plugin settings and fill in the required information. You need to provide an API key to be used for authenticating with the REST API. Then you need to give WordPress the Node application url what to ping for renders. Last you can define the interval when the renders expire.

In the WordPress template you need to request the rendered content before initial render of the header. You can request the content of the application by using `WPSSR\Render::render` method which takes in three parameters:

- `string $url` Url of the page you are requesting to be rendered
- `string $app_selector` Query selector for the application root element
- `string $waitfor_selector` Query selector for the puppeteer to wait before rendering the page

```php
<?php
/**
 * Template Name: React Application
 *
 */

$content = WPSSR\Render::render(
  get_permalink(),
  '#react-app-root', // App selector
  '#react-app-inner' // Waitfor selector
);

get_header();
?>

<div id="react-app-root">
  <?php echo $content; ?>
</div>

<?php get_footer(); ?>
```

### Node App

To start the Node application you need to have couple of environment variables available for it.

- `WP_DOMAIN` which points to the root of your WordPress installation
- `API_KEY` is the same API key that you provided in the plugin settings page
- `NODE_ENV` to define the Node environment. This is used in example to ignore puppeteer HTTPS errors
- `SCHEDULE` to define the schedule for rendering cron job

One way to run the application is to use the Docker image provided:

```bash
docker run -d \
  -e "WP_DOMAIN=https://example.com" \
  -e "API_KEY=my-api-key" \
  -e "NODE_ENV=production" \
  -e "SCHEDULE=*/15 * * * *" \
  -p 80:3000 \
  --restart=unless-stoppped \
  --name=wpssr \
  columbiaroadcom/wp-ssr
```

## Publishing package
The composer package is automatically published to `columbiaroad/wp-ssr` when a github release is made.

To publish to dockerhub you need access to the repository there. Publishing is done as follows:
```bash
docker build . --tag columbiaroadcom/wp-ssr:<tag>
docker push columbiaroadcom/wp-ssr:<tag>
```


## TODO

- [ ] Add `.env` file support for the Node application
- [ ] Optimize for large amount of renders
- [ ] Lock the rendering process to prevent multiple simultaneous render calls
