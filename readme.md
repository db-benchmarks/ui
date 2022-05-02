<p align="center">
  <a href="https://db-benchmarks.com" target="_blank" rel="noopener">
    <img src="./logo.svg" width="50%" alt="db-benchmarks logo" style="color: white">
  </a>
</p>

<h3 align="center">
  <a href="https://db-benchmarks.com">📊 Benchmark results</a> •
  <a href="#introduction">Intro</a> •
  <a href="https://github.com/db-benchmarks/db-benchmarks">Test framework</a> •
  <a href="#installation">Installation</a>
</h3>

<p>&nbsp;</p>

# Introduction

This repository is the UI component of the test framework which includes 100% of code used to run https://db-benchmarks.com/ . You can easily setup your own version of the site for your local benchmarks.

**Read about the project in general in its main repo - https://github.com/db-benchmarks/db-benchmarks**

Please [find instructions here](https://github.com/db-benchmarks/db-benchmarks#save-to-db-to-visualize) on populating your local database with test results.

## Installation

Clone the repository and update the submodules:
```bash
git clone https://github.com/db-benchmarks/ui
cd ui
git submodule update --init --recursive --remote
```

Copy `.env_example` to `.env`:
```
cp .env_example .env
```

Update file `.env` with the actual hostname of the site, don't change `NGINX_TEMPLATES_DIR`.
```bash
docker-compose up -d
```

In a minute the web server should be accessible at `http://HOSTNAME`, db - at `http://DB_USERNAME:DB_PASSWORD@db.HOSTNAME`

# HTTPS support

By default it listens on port 80, but you can easily install Let's Encrypt certificates. Just run:

```bash
docker-compose exec nginx certbot --nginx
```

and answer certbot's questions (your email etc). After that run:

```bash
docker-compose exec nginx sed -i "s/listen 443 ssl;/listen 443 ssl http2;/" /etc/nginx/conf.d/site.conf
docker-compose restart nginx
```

to enable http/2 for the site.

It will put the certificates to `./nginx/ssl/` and will update your Nginx configs in `./nginx/conf/`. After that to prevent the configs from further overriding from the templates in `./nginx/templates/` you need to update your `.env` file like this:

```
- NGINX_TEMPLATES_DIR=/etc/nginx/templates
+ NGINX_TEMPLATES_DIR=/tmp/
```

To renew your certificates in few months just run:

```bash
docker-compose exec nginx certbot renew
docker-compose exec nginx sed -i "s/listen 443 ssl;/listen 443 ssl http2;/" /etc/nginx/conf.d/site.conf
docker-compose restart nginx
```
