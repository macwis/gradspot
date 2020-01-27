# GradSpot

Project name from two names combined.


## Core files

`CrawlerController.php`

Contains basic skeleton. After running the server go to `http://localhost:8000/api/crawler/spotify`.

`SpotifyHelper.php`

Contains Symfony custom service to crawl the job postings including couple of more universal static functions.



## How to run?

1. `git clone git@github.com:macwis/gradspot.git`
2. `composer install`
3. And with that just hit:

`symfony server:start`

For DEMO: `http://localhost:8000/api/crawler/spotify?debug=true`

Adding param `debug=true` limits the crawl just to first page. If you have a bit more time
you can easily go full scale with around 128 jobs...

For all postings in Sweden: `http://localhost:8000/api/crawler/spotify`



## How to remake the project or integrate with an existing one?

Basically the two files are all you need for your Symfony setup. All the rest can be regenerated in few steps or reused from the existing project (if you have one).

If you choose this path - make sure you have available annotations and goutte.
