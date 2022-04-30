---
weight: 3
title: "1.1 million comments from Hacker News: small data full-text / analytics test"
date: 2022-04-11
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

In this test we use the data collection of 1.1M [Hacker News](https://news.ycombinator.com/) curated comments with numeric fields from https://zenodo.org/record/45901. In the modern world 1 million of documents can be considered a very small data set which, however, can be typical for many applications: blogs and news sites, online stores, job, automotive and real estate sites and so on. It's typical for such applications to have:
* not very long textual data in one or multiple fields
* and a number of attributes

<!--more-->

## Data collection

The source of the data collection is https://zenodo.org/record/45901.

The record structure is:

```json
"properties": {
   "story_id": {"type": "integer"},
   "story_text": {"type": "text"},
   "story_author": {"type": "text", "fields": {"raw": {"type":"keyword"}}},
   "comment_id": {"type": "integer"},
   "comment_text": {"type": "text"},
   "comment_author": {"type": "text", "fields": {"raw": {"type":"keyword"}}},
   "comment_ranking": {"type": "integer"},
   "author_comment_count": {"type": "integer"},
   "story_comment_count": {"type": "integer"}
}
```

## Databases

So far we have made this test available for 3 databases:
* [Clickhouse](https://github.com/ClickHouse/ClickHouse) - a powerful OLAP database,
* [Elasticsearch](https://github.com/elastic/elasticsearch) - general purpose "search and analytics engine",
* [Manticore Search](https://github.com/manticoresoftware/manticoresearch/) - "database for search", Elasticsearch alternative.

We've tried to make as little changes to database default settings as possible to not give either of them an unfair advantage:

* Clickhouse: [no tuning](https://github.com/db-benchmarks/db-benchmarks/blob/main/tests/hn/ch/init), just `CREATE TABLE ... ENGINE = MergeTree() ORDER BY id SETTINGS index_granularity = 8192` and standard [clickhouse-server](https://github.com/db-benchmarks/db-benchmarks/blob/main/docker-compose.yml) docker image.
* Elasticsearch: also [no tuning](https://github.com/db-benchmarks/db-benchmarks/tree/main/tests/hn_small/es/logstash) except for `bootstrap.memory_lock=true` since as said on https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html#_disable_swapping it needs to be done for performance.
* [Manticore Search](https://github.com/db-benchmarks/db-benchmarks/blob/main/tests/hn/manticore/generate_manticore_config.php):
  - `min_infix_len = 2` since in Elasticsearch by default you can do infix full-text search and it would be unfair advantage if Manticore was running w/o infixes. Unfortunately it's not possible in Clickhouse at all, so it's given the handicap.

{{% embed file="../about-internal-caches" %}}

## Queries

The query set consists of both full-text and analytical (filtering, sorting, grouping, aggregating) queries:

{{% code file="../../db-benchmarks/tests/hn_small/test_queries" language="json" %}}


## Results

You can find all the results on the [results page](/?cache=fast_avg&tests=hn) by selecting "Test: hn_small". {{% embed file="../about-results" %}}

**Unlike other less transparent and less objective benchmarks we are not making any conclusions, we are just leaving screenshots of the results here:**

### 3 competitors at once

[avg(80% fastest)](/?cache=fast_avg&engines=manticoresearch_plain_20220422_066f_da31%2Celasticsearch%2Cclickhouse&tests=hn_small&memory=110000&queries=0%2C1%2C2%2C3%2C4%2C5%2C6%2C7%2C8%2C9%2C10%2C11%2C12%2C13%2C14%2C15%2C16%2C17%2C18%2C19%2C20%2C21%2C22%2C23%2C24%2C25%2C26%2C27):

![](ch_ms_es.png)

### Clickhouse vs Elasticsearch

[avg(80% fastest)](/?cache=fast_avg&engines=elasticsearch%2Cclickhouse&tests=hn_small&memory=110000&queries=0%2C1%2C2%2C3%2C4%2C5%2C6%2C7%2C8%2C9%2C10%2C11%2C12%2C13%2C14%2C15%2C16%2C17%2C18%2C19%2C20%2C21%2C22%2C23%2C24%2C25%2C26%2C27:)
![](ch_es.png)

### Manticore Search vs Elasticsearch

[avg(80% fastest)](/?cache=fast_avg&engines=manticoresearch_plain_20220422_066f_da31%2Celasticsearch&tests=hn_small&memory=110000&queries=0%2C1%2C2%2C3%2C4%2C5%2C6%2C7%2C8%2C9%2C10%2C11%2C12%2C13%2C14%2C15%2C16%2C17%2C18%2C19%2C20%2C21%2C22%2C23%2C24%2C25%2C26%2C27):
![](ms_es.png)

### Manticore Search vs Clickhouse

[avg(80% fastest)](/?cache=fast_avg&engines=manticoresearch_plain_20220422_066f_da31%2Cclickhouse&tests=hn_small&memory=110000&queries=0%2C1%2C2%2C3%2C4%2C5%2C6%2C7%2C8%2C9%2C10%2C11%2C12%2C13%2C14%2C15%2C16%2C17%2C18%2C19%2C20%2C21%2C22%2C23%2C24%2C25%2C26%2C27):
![](ms_ch.png)

## Disclaimer

{{% embed file="../disclaimer" %}}
