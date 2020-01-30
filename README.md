# GradSpot

Project name from two names combined.



## Core files

`CrawlerController.php`

Contains basic skeleton. After running the server go to `http://localhost:8000/api/crawler/spotify`.

`SpotifyHelper.php`

Contains Symfony custom service to crawl the job postings including couple of more universal static functions.



## How to run?

1. `git clone git@github.com:macwis/gradspot.git`
2. `cd gradspot && composer install`
3. And with that just hit: `symfony server:start`

For DEMO: `http://localhost:8000/api/crawl/spotify?demo=true`

Adding param `demo=true` limits the crawl just to first page. If you have a bit more time
you can easily go full scale ...

For all postings in Sweden: `http://localhost:8000/api/crawl/spotify`



## Tests

Tests are included in:

`tests/Service/SpotifyHelperTest.php`

To run tests simply execute Symfony tests.

`./bin/phpunit`

Also, I advise to check the coverage report (requires xdebug installed):

`./bin/phpunit --verbose --coverage-text --coverage-html reports/`

Generated HTML report will be available in `reports` directory.

```
 Summary:                 
  Classes: 100.00% (2/2)  
  Methods: 100.00% (13/13)
  Lines:   100.00% (66/66)

\App\Controller::App\Controller\CrawlerController
  Methods: 100.00% ( 1/ 1)   Lines: 100.00% (  5/  5)
\App\Service::App\Service\SpotifyHelper
  Methods: 100.00% (12/12)   Lines: 100.00% ( 61/ 61)
```


## Documentation

Documentation has been generated using phpDocumentor and is available in folder `docs`.

To regenerate it there has to be phpDocumentor available:

`phpDocumentor -d ./src -t docs`




## How to remake the project or integrate with an existing one?

Basically the two files are all you need for your Symfony setup. All the rest can be regenerated in few steps or reused from the existing project (if you have one).

If you choose this path - make sure you have available annotations and goutte.
