<?php
/*
 * Basic Symfony service to get job postings from Spotify.
 */

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Spotify crawler Symfony service class.
 */
class SpotifyHelper
{
    /**
     * Predefined spotify job page URL.
     */
    const URL = 'https://www.spotifyjobs.com/wp-admin/admin-ajax.php';
    /**
     * Predefined spotify job page URL params.
     */
    const URL_PARAMS = [
        'action' => 'get_jobs',
        'pageNr' => 1,
        'perPage' => 16,
        'featuredJobs' => '',
        'category' => 0,
        'location' => 0,
        'search' => '',
        'locations[]' => 'sweden',
    ];
    /**
     * Predefined set of options for the request.
     */
    const URL_OPTIONS = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => '',
        ],
    ];

    /**
     * @var LoggerInterface Logger interface
     */
    private $logger;
    /**
     * @var array Storage of the loaded job post items
     */
    private $allItems = [];
    /**
     * @var array Buffer for the current page processing
     */
    private $pageItems = [];

    /**
     * Class instance constructor.
     *
     * @param null $httpClient
     * @param LoggerInterface $logger Logger interface
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->httpClient = new Client();
    }

    /**
     * Method to run the crawling of jobs for Spotify Sweden.
     *
     * @param int $pagesLimit Limit the number of pages for crawling
     *
     * @return array
     */
    public function loadItems($pagesLimit = 0)
    {
        $this->allItems = [];
        $pageNr = 1;
        // Stop the loop when there are no more new items
        // and when the pages limit is exceeded.
        do {
            $this->loadJobPostList($pageNr);
            foreach ($this->pageItems as &$item) {
                // Enrich with a description
                $item['description'] = $this->loadJobPostDetails($item['url']);
            }
            $this->allItems = array_merge($this->allItems, $this->pageItems);
            $this->quickLog('Another page of job posts retrieved!');
            // Increment the page counter
            ++$pageNr;
        } while (\count($this->pageItems) > 0
                    and ! ($pagesLimit != 0 and $pageNr > $pagesLimit));
    }

    /**
     * Using the regexp iterate through and populate years_required property.
     *
     * @param array $jobposts An job posts collection to be used for the detection
     */
    public function addDetectedYears()
    {
        foreach ($this->allItems as &$jobpost) {
            $jobpost['yearsRequired'] = self::getYears($jobpost['description']);
        }
    }

    /**
     * Extracts the number of years from the job post description.
     *
     * @param  mixed $description Job post description
     *
     * @return string Descriptive years of experience
     */
    public static function getYears($description)
    {
        if (preg_match('(([\w\-\+]+)\s+years)', $description, $matches)) {
            return $matches[1];
        }
        return 'n/a';
    }

    /**
     * Checks if any of the given from an array is in the string.
     *
     * @param string Input string
     * @param array A collection of strings to check
     *
     * @return boolean Result true or false
     */
    public static function contains($str, array $arr)
    {
        foreach ($arr as $a) {
            if (false !== stripos($str, $a)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detects the experience level of a job post.
     *
     * @param string Input string headline
     * @param string Input string description
     *
     * @return int Result number 0-3
     */
    public static function detectExperience($headline, $description)
    {
        $level = 0;
        if (self::contains($description, ['junior', 'internship'])) {
            $level = 1;
        } elseif (self::contains($headline, ['senior', 'manager'])) {
            $level = 3;
        } elseif (self::contains($description, ['experience', 'experienced'])) {
            $level = 2;
        } else {
            $level = 0;
        }
        return $level;
    }

    /**
     * Getter method to get all job post loaded.
     *
     * @return array All job posts currently in processing
     * @throws \Exception Notify about not loaded data
     */
    public function getAllItems()
    {
        if (count($this->allItems) > 0) {
            return $this->allItems;
        }
        throw new \Exception('No loaded job posts! Try loadItems() first!');
    }

    /**
     * Trying to estimate the expected experience level.
     * I have divided into 3 basic categories:
     *   3 - Senior/Manager
     *   2 - Somehow experienced
     *   1 - Junior/Intern
     *   0 - Unknown.
     */
    public function addDetectedExperience()
    {
        foreach ($this->allItems as &$jobpost) {
            $jobpost['experienceLevel'] = self::detectExperience($jobpost['headline'], $jobpost['description']);
        }
    }

    /**
     * Option to pass customized HTTP client class, helpful for unit-tests to pass a mocked client for requests.
     *
     * @param GuzzleHttp $httpClient
     */
    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * POST request method to retrieve the data.
     *
     * @param int $pageNr Numer of page to crawl
     *
     * @throws \RuntimeException Exception when the HTTP POST request for the data fails
     */
    private function loadJobPostList(int $pageNr = 1)
    {
        $params = self::URL_PARAMS;  // use default params set
        $params['pageNr'] = $pageNr;  // overwrite page nr param
        $response = $this->httpClient->request('POST', self::URL, ['form_params' => $params]);
        $jsonData = json_decode($response->getBody());
        $this->cleanUpAndLoad($jsonData->data->items);
    }

    /**
     * Converts job posts stdObjs into array.
     *
     * @param array $arrayOfStdObjs An job posts collection to be used for the detection
     */
    private function cleanUpAndLoad(&$arrayOfStdObjs)
    {
        $this->pageItems = [];
        foreach ($arrayOfStdObjs as $stdObj) {
            // Let's make a match for the field name with the task description
            $stdObj->headline = $stdObj->title;
            // Remove the unnecessary information for the task
            unset($stdObj->title, $stdObj->locations, $stdObj->categories);
            // unify the format to an array
            $this->pageItems[] = get_object_vars($stdObj);
        }
    }

    /**
     * Extracts job posting description from a linked page.
     *
     * @param string $url A job post page url
     *
     * @return string Job description
     */
    private function loadJobPostDetails($url)
    {
        $this->quickLog("Getting description from $url");
        $response = $this->httpClient->request('GET', $url);
        $content = $response->getBody();
        $startPos = stripos($content, '<div class="column-inner">');
        $endPos = stripos($content, '<div class="col-md-4 col-lg-3">');
        return trim(substr($content, $startPos, $endPos - $startPos));
    }

    /**
     * Wrapper function for the logging.
     *
     * @param string $msg Message to be written in the logs
     *
     * @return void
     */
    private function quickLog($msg)
    {
        if ($this->logger !== null) {
            $this->logger->info($msg);
        }
    }
}
