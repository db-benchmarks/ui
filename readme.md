<p align="center">
  <a href="https://db-benchmarks.com" target="_blank" rel="noopener">
    <img id="intro" src="./logo.svg" width="50%" alt="db-benchmarks logo" style="color: white">
  </a>
</p>

<h3 align="center">
  <a href="https://db-benchmarks.com">📊 Benchmark results</a> •
  <a href="#introduction">Intro</a> •
  <a href="https://github.com/db-benchmarks/db-benchmarks/#intro">Test framework</a> •
  <a href="#installation">Installation</a>
</h3>

<p>&nbsp;</p>

# Introduction

This repository is the UI component of the test framework which includes 100% of code used to run https://db-benchmarks.com/ . You can easily setup your own version of the site for your local benchmarks.

**Read about the project in general in its main repo - https://github.com/db-benchmarks/db-benchmarks**

Please [find instructions here](https://github.com/db-benchmarks/db-benchmarks#save-to-db-to-visualize) on populating your local database with test results.

## Installation

1. [Add](https://linuxize.com/post/how-to-edit-your-hosts-file/) `db.localhost` to your hosts file unless you want to run it on a specific hostname.
2. Clone the repository, make `.env`, update the submodule, run the containers and populate the database:
```bash
git clone git@github.com:db-benchmarks/ui.git db-benchmarks-site
cd db-benchmarks-site
cp .env_example .env
# update .env if you want to run it on specific hostname, otherwise it will run on localhost and db.localhost
git submodule update --init --remote site/db-benchmarks
docker-compose up -d

# wait until it fully starts, then in another terminal tab:
./site/db-benchmarks/test --save=./site/db-benchmarks/results --host=db.localhost --port=80 --username=bench --password=bench

# stop the docker-compose and start it with -d, so it runs in background
docker-compose up -d
```

This should run a full copy of https://db-benchmarks.com on http://localhost (with the db running at http://bench:bench@db.localhost).

To modify the hostname, db username/password update `.env` and:
```
docker-compose down
docker-compose up -d
```

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

# Notes

* The original test results layout was heavily inspired by Clickhouse Benchmarks - https://clickhouse.com/benchmark/dbms/ . Thank you, Alexey Milovidov and Clickhouse team!
* To enable Google analytics add `VUE_APP_GA` to `.env` and `params.analytics.google.id` to site's `config.toml`

# Development environment
For local development the suite provides `docker-compose-dev.yml` with enabled x-debug in the php for using breakpoints, and the default Nginx listen port changed to `8080`.

#### Backend

`index.php` gets mounted as a volume into the docker container, so you don't need to rebuild that after each change in the backend.
Just run it from `localhost:8080/api`

#### Frontend

Add backend API URL `VUE_APP_API_URL="http://localhost:8080"` to `/frontend/.env` and run `vue-cli-service serve`. That's all you need for local frontend development.
