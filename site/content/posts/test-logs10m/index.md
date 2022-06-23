---
weight: 4
title: "10 million Nginx logs"
date: 2022-04-13
draft: false
author: "Sergey Nikolaev"
authorLink: "https://twitter.com/sanikolaev"
resources:
- name: "featured-image"
  src: "featured-image.jpg"

lightgallery: true

toc:
  auto: false
---

## Intro

In this article we'll observe another test added to https://db-benchmarks.com/ - 10+ million standard HTTP logs collected by Nginx on ecommerce website zanbil.ir.

<!--more-->

## Data collection

We found the data collection on https://www.kaggle.com/datasets/eliasdabbas/web-server-access-logs and found it very interesting to make a test with since the dataset represents a very standard nginx http access log. Here's an example:

```
54.36.149.41 - - [22/Jan/2019:03:56:14 +0330] "GET /filter/27|13%20%D9%85%DA%AF%D8%A7%D9%BE%DB%8C%DA%A9%D8%B3%D9%84,27|%DA%A9%D9%85%D8%AA%D8%B1%20%D8%A7%D8%B2%205%20%D9%85%DA%AF%D8%A7%D9%BE%DB%8C%DA%A9%D8%B3%D9%84,p53 HTTP/1.1" 200 30577 "-" "Mozilla/5.0 (compatible; AhrefsBot/6.1; +http://ahrefs.com/robot/)" "-"
31.56.96.51 - - [22/Jan/2019:03:56:16 +0330] "GET /image/60844/productModel/200x200 HTTP/1.1" 200 5667 "https://www.zanbil.ir/m/filter/b113" "Mozilla/5.0 (Linux; Android 6.0; ALE-L21 Build/HuaweiALE-L21) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.158 Mobile Safari/537.36" "-"
31.56.96.51 - - [22/Jan/2019:03:56:16 +0330] "GET /image/61474/productModel/200x200 HTTP/1.1" 200 5379 "https://www.zanbil.ir/m/filter/b113" "Mozilla/5.0 (Linux; Android 6.0; ALE-L21 Build/HuaweiALE-L21) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.158 Mobile Safari/537.36" "-"
40.77.167.129 - - [22/Jan/2019:03:56:17 +0330] "GET /image/14925/productModel/100x100 HTTP/1.1" 200 1696 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
91.99.72.15 - - [22/Jan/2019:03:56:17 +0330] "GET /product/31893/62100/%D8%B3%D8%B4%D9%88%D8%A7%D8%B1-%D8%AE%D8%A7%D9%86%DA%AF%DB%8C-%D9%BE%D8%B1%D9%86%D8%B3%D9%84%DB%8C-%D9%85%D8%AF%D9%84-PR257AT HTTP/1.1" 200 41483 "-" "Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:16.0)Gecko/16.0 Firefox/16.0" "-"
40.77.167.129 - - [22/Jan/2019:03:56:17 +0330] "GET /image/23488/productModel/150x150 HTTP/1.1" 200 2654 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
40.77.167.129 - - [22/Jan/2019:03:56:18 +0330] "GET /image/45437/productModel/150x150 HTTP/1.1" 200 3688 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
40.77.167.129 - - [22/Jan/2019:03:56:18 +0330] "GET /image/576/article/100x100 HTTP/1.1" 200 14776 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
66.249.66.194 - - [22/Jan/2019:03:56:18 +0330] "GET /filter/b41,b665,c150%7C%D8%A8%D8%AE%D8%A7%D8%B1%D9%BE%D8%B2,p56 HTTP/1.1" 200 34277 "-" "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)" "-"
40.77.167.129 - - [22/Jan/2019:03:56:18 +0330] "GET /image/57710/productModel/100x100 HTTP/1.1" 200 1695 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
207.46.13.136 - - [22/Jan/2019:03:56:18 +0330] "GET /product/10214 HTTP/1.1" 200 39677 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
40.77.167.129 - - [22/Jan/2019:03:56:19 +0330] "GET /image/578/article/100x100 HTTP/1.1" 200 9831 "-" "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)" "-"
```

Probably most of web sites in the Internet have a similar log. Many website admins and devops want to be able to process logs like this to do filtering and analytics.

After parsing by the [framework](https://github.com/db-benchmarks/db-benchmarks/) there are 11 fields in the log:
* 7 string fields
* 4 integer fields

The whole list of fields and their data types is:

```json
"properties": {
  "remote_addr": {"type": "text"},
  "remote_user": {"type": "text"},
  "runtime": {"type": "long"},
  "time_local": {"type": "long"},
  "request_type": {"type": "text"},
  "request_path": {
    "type": "text",
    "fields": {
      "raw": {
        "type": "keyword"
      }
    }
  },
  "request_protocol": {"type": "text"},
  "status": {"type": "long"},
  "size": {"type": "long"},
  "referer": {"type": "text"},
  "usearagent": {"type": "text"}
}
```

We preliminarily [convert](https://github.com/db-benchmarks/db-benchmarks/tree/main/tests/logs10m/prepare_csv) the raw log to CSV so it's easier to load to different databases and search engines.

## Databases

So far we have made this test available for 3 databases:
* [Clickhouse](https://github.com/ClickHouse/ClickHouse) - a powerful OLAP database,
* [Elasticsearch](https://github.com/elastic/elasticsearch) - general purpose "search and analytics engine",
* [Manticore Search](https://github.com/manticoresoftware/manticoresearch/) - "database for search", Elasticsearch alternative.

{{% embed file="../about-needed-settings" %}}

* Clickhouse: [no tuning](https://github.com/db-benchmarks/db-benchmarks/blob/main/tests/logs10m/ch/init), just `CREATE TABLE ... ENGINE = MergeTree() ORDER BY id` and standard [clickhouse-server](https://github.com/db-benchmarks/db-benchmarks/blob/main/docker-compose.yml) docker image.
* Elasticsearch: we test in 2 modes:
  - with [no tuning](https://github.com/db-benchmarks/db-benchmarks/blob/main/tests/logs10m/es/logstash/template.json) at all which is probably what most users do
  - with number of [shards equal to the number of CPU cores](https://github.com/db-benchmarks/db-benchmarks/blob/main/tests/logs10m/es/logstash_tuned/template.json) on the server, so Elasticsearch can utilize the CPUs more efficiently for lower response time, since as [said](https://www.elastic.co/guide/en/elasticsearch/reference/current/size-your-shards.html#single-thread-per-shard) in Elasticsearch official guide "Each shard runs the search on a single CPU thread". The dataset size is only 3.5 GB, so it's not clear if it's required or not, but that's why we are testing it.
  - `bootstrap.memory_lock=true` since as said on https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html#_disable_swapping it needs to be done for performance.
  - the docker image is [standard](https://github.com/db-benchmarks/db-benchmarks/blob/main/docker-compose.yml)
* Manticore Search is used in a form of [their official docker image + the columnar library they provide](https://github.com/db-benchmarks/db-benchmarks/blob/main/docker-compose.yml):
  - we test Manticore's default row-wise storage
  - and columnar storage since Elasticsearch and Clickhouse don't provide row-oriented stores and it may be more fair to compare with Manticore running in this mode.
  - we added `secondary_indexes = 1` to the config which enables secondary indexes while filtering (when loading data that's built anyway). Since Elasticsearch uses secondary indexes by default and it's fairly easy to enable the same in Manticore it makes sense to do it. Unfortunately in Clickhouse user would have to make quite an effort to do the same, hence it's not done, since it would then be considered a heavy tuning which would then require further tuning of the other databases which would make things too complicated and unfair.

{{% embed file="../about-internal-caches" %}}

## Queries

The queries are mostly analytical that do filtering, sorting and grouping, but we've also included one full-text query which searches in the request URL:
{{% code file="../../db-benchmarks/tests/logs10m/test_queries" language="json" %}}

## Results

You can find all the results on the [results page](/) by selecting "Test: logs10m". {{% embed file="../about-results" %}}

**Unlike other less transparent and less objective benchmarks we are not making any conclusions, we are just leaving screenshots of the results here:**

### 3 competitors with no tuning at once

![](msr_es_ch.png)

Unfortunately Elasticsearch timed out for 2 queries, hence they were excluded from the final score calculation.

### Elasticsearch with no tuning vs Manticore Search (default row-wise storage)

![](es_msr.png)

Unfortunately Elasticsearch timed out for 2 queries, hence they were excluded from the final score calculation.

### Elasticsearch with no tuning vs tuned

![](es_est.png)

Unfortunately Elasticsearch timed out for 2 queries, hence they were excluded from the final score calculation.

### Elasticsearch tuned vs Manticore Search (default row-wise storage)

![](est_msr.png)

Unfortunately Elasticsearch timed out for 2 queries, hence they were excluded from the final score calculation.

### Elasticsearch tuned vs Manticore Search (columnar storage)

![](est_msc.png)

Unfortunately Elasticsearch timed out for 2 queries, hence they were excluded from the final score calculation.

### Clickhouse vs Manticore Search (columnar storage)

![](ch_msc.png)

### Manticore Search row-wise vs columnar

![](msr_msc.png)

## Disclaimer

{{% embed file="../disclaimer" %}}
