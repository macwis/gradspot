<?php
/*
 * Basic Symfony service to get job postings from Spotify.
 */

namespace App\Service;

use Goutte\Client;
use Psr\Log\LoggerInterface;

/**
 * Spotify crawler Symfony service class
 */
class SpotifyHelper
{
    /**
     * Predefined spotify job page URL
     */
    const URL = 'https://www.spotifyjobs.com/wp-admin/admin-ajax.php';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var array
     */
    private $allItems;

    /**
     * @param LoggerInterface $logger Logger interface
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Method to run the crawling of jobs for Spotify Sweden.
     *
     * @param boolean $pagesLimit Limit the number of pages for crawling
     *
     * @return array
     */
    public function run($pagesLimit = 0)
    {
        $this->allItems = [];
        $newItems = true;
        $pageNr = 1;
        do {
            $newItems = ($this->spotifyCrawler($pageNr)->data->items);
            foreach ($newItems as &$item) {
                // Let's make a match for the field name with the task description
                $item->headline = $item->title;
                // Enrich the object with a description
                $item->description = $this->extractSpotifyJobPostDescription($item->url);
                // Remove the unnecessary information for the task
                unset($item->title, $item->locations, $item->categories);
            }
            $this->allItems = array_merge($this->allItems, $newItems);
            $newCount = \count($newItems);
            $this->quickLog("Another $newCount of job posts retrieved!");
            // Symfony allows better, but for the sake of quick demo,
            // hack for debugging: no of items limit if &debug=true present in URL.
            if ($pageNr >= $pagesLimit) {
                break;
            }
            // Increment the page counter
            ++$pageNr;
        } while ($newCount > 0);
        return $this->allItems;
    }

    /**
     * POST request method to retrieve the data.
     *
     * @param int $pageNr Numer of page to crawl
     *
     * @return array JSON data from the HTTP POST request
     *
     * @throws \RuntimeException Exception when the HTTP POST request for the data fails
     */
    private function spotifyCrawler(int $pageNr = 1)
    {
        $data = [
            'action' => 'get_jobs',
            'pageNr' => $pageNr,
            'perPage' => 16,
            'featuredJobs' => '',
            'category' => 0,
            'location' => 0,
            'search' => '',
            'locations[]' => 'sweden',
        ];
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ],
        ];
        $context = stream_context_create($options);
        $result = file_get_contents(self::URL, false, $context);
        if (false === $result) {
            throw new \RuntimeException(sprintf('Request to Spotify failed!'));
        }
        $jsonData = json_decode($result);
        return $jsonData;
    }

    /**
     * Using the regexp iterate through and populate years_required property.
     *
     * @param array $jobposts An job posts collection to be used for the detection
     *
     * @return void
     */
    public function detectYears(&$jobposts)
    {
        foreach ($jobposts as &$jobpost) {
            $jobpost->yearsRequired = $this->getYears($jobpost->description);
        }
    }

    /**
     * Trying to estimate the expected experience level.
     * I have divided into 3 basic categories:
     *   3 - Senior/Manager
     *   2 - Somehow experienced
     *   1 - Junior/Intern
     *   0 - Unknown.
     *
     * @param array $jobposts An job posts collection to be used for the detection
     *
     * @return void
     */
    public function detectExperience(&$jobposts)
    {
        foreach ($jobposts as &$jobpost) {
            if ($this->contains($jobpost->headline, ['senior', 'manager'])) {
                $level = 3;
            } elseif ($this->contains($jobpost->description, ['experience', 'experienced'])) {
                $level = 2;
            } elseif ($this->contains($jobpost->description, ['junior', 'internship'])) {
                $level = 1;
            } else {
                $level = 0;
            }
            $jobpost->experienceLevel = $level;
        }
    }

    /**
     * Converts job posts stdObjs into array.
     *
     * @param array $jobposts An job posts collection to be used for the detection
     *
     * @return void
     */
    public function cleanUp(&$jobposts)
    {
        foreach ($jobposts as &$jobpost) {
            $jobpost = get_object_vars($jobpost);
        }
    }

    /**
     * Extracts job posting description from a linked page.
     *
     * @param string $url A job post page url
     *
     * @return string Job description
     */
    private function extractSpotifyJobPostDescription($url)
    {
        $client = new Client();
        $this->quickLog("Getting description from $url");
        $crawler = $client->request('GET', $url);
        return $crawler->filter('.column-inner')->html();
    }

    /**
     * Extracts the number of years from the job post description.
     *
     * @param  mixed $description Job post description
     *
     * @return string Descriptive years of experience
     */
    public function getYears($description)
    {
        if (preg_match('(([\w\-\+]+)\s+years)', $description, $matches)) {
            return $matches[1];
        }
        return 'n/a';
    }

    /**
     * Checks if any of the given from an array is in the string
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
     * Wrapper function for the logging
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
