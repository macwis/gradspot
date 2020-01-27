# GradSpot

Project name from two names combined.


## Core files

`CrawlerController.php`

Contains basic skeleton. After running the server go to `http://localhost:8000/api/crawler/spotify`.

`SpotifyHelper.php`

Contains Symfony custom service to crawl the job postings including couple of more universal static functions.


## How to remake the project?

Basically the two file above are all you need.. because all the rest can be regenerated in few steps:

1. `composer create-project symfony/skeleton gradspot`

Installation of dependencies:

2. `composer require annotations`
3. `composer require fabpot/goutte`


## How to run?

Make sure you have available annotations and goutte.

And with that just hit:

- `symfony server:start`

For DEMO: `http://localhost:8000/api/crawler/spotify?debug=true`

Adding param `debug=true` limits the crawl just to first page. If you have a bit more time
you can easily go full scale with around 128 jobs...

For all postings in Sweden: `http://localhost:8000/api/crawler/spotify`
