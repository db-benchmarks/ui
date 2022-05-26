---
weight: 5
title: "10 principles of proper database benchmarking"
date: 2022-05-26
draft: false
author: "Sergey Nikolaev"
authorLink: "https://twitter.com/sanikolaev"
resources:
- name: "featured-image"
  src: "image.jpg"

lightgallery: true

toc:
  enable: false
---

At https://db-benchmarks.com/ we test different open source databases and search engines and develop an open source platform so you can do it too. In this article I would like to share 10 most important principles that we've formulated for ourselves that help us make high quality benchmarks.

<!--more-->

1. **Test different databases on exactly same hardware.** In a number of database benchmarks I've seen people benchmark competitors on different hardware. For example, in [Druid vs Clickhouse vs Rocket](https://imply.io/blog/druid-nails-cost-efficiency-challenge-against-clickhouse-and-rockset/) benchmark they say "We actually wanted to do the benchmark on the same hardware, an m5.8xlarge, but the only pre-baked configuration we have for m5.8xlarge is actually the m5d.8xlarge … Instead, we run on a c5.9xlarge instance". Bad news, guys: when you run benchmarks on different hardware, at the very least you can't then say that something is "106.76%" and "103.13%" of something else. Even when you test on the same bare-metal server it's quite difficult to get coefficient of variation lower than 5%. 3% difference on different servers can be highly likely ignored. Provided all that, how can one make sure the final conclusion is true?
2. **Test with full OS cache purged before each test.** At DB Benchmarks we specialize in latency testing. We make sure some query against some database takes 117ms today, tomorrow and in a week. That's a fundamental thing in the platform, without it nothing else matters. It's hard. To make it happen it's important to make sure that when you test a query the environment is exactly the same as previous time. One of the things people always forget about is purging OS cache. If you don't it chances are you'll have part of the data your query has to read from disk already in memory which will make the result unstable.
3. **Measure cold run separately.** Disks, be it an NVMe or an HDD are all still significantly slower than RAM. People that do benchmarks often don't pay enough attention to it, while it's important, especially for analytical queries and analytical databases where cold queries may happen often. So the principle is: measure cold run time separately. Otherwise you completely hide the results of how database can handle I/O.
4. **Database which is being tested should have all it's internal caches disabled.** Another related thing is to disable internal database caches. Otherwise you'll just measure cache performance which might also make sense for some tests, but normally it's not what you want.
5. **Nothing else should be running during testing.** Otherwise your test results may be just very unstable since your database will have to compete with another process.
6. **You need to restart database before each query.** Otherwise previous queries can still impact current query's response time, despite clearing internal caches.
7. **You need to wait until the database warms up completely after it's started.** Otherwise you can at least end up competing with db's warmup process for I/O which can spoil your test results severely.
8. **Test on a fixed CPU frequency.** Otherwise if you are using “on-demand” CPU governor (which is normally a default) it can easily turn your 500ms response time into a 1000+ ms.
9. **Test on SSD/NVME rather than HDD.** Otherwise depending on where your files are located on HDD you can get up to 2x lower/higher I/O performance (no joking, 2x), which can make at least your cold queries results wrong.
10. **Most important: more repetitions and control over CV.** It's probably the most common mistake in latency benchmarking: people run a query 2–3 time, calculate an average and that's it. In most cases few attempts is not enough, the next time you run the same query you can get 50% different result. But there's a way you can improve and control it: do more repetitions and control coefficient of variation. CV is a very good metric which shows how stable your test results are. If it's higher than N% you can't say one database is N% faster than another. So just make enough repetitions until your CV is low enough (e.g. 3%). If you never reach it means you didn't do your best following the above principles.

I hope this article will help you improve your benchmarks. If you want something ready take a look at https://db-benchmarks.com/ . It's a 100% free and open source (including the very raw test results) benchmarks platform and framework that can help you test your databases and queries easier and faster.
