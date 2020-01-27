<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Goutte\Client;

class SpotifyHelper
{
    private $logger;
    private $all_items;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Method to run the crawling of jobs for Spotify Sweden.
     */
    public function run()
    {
        $this->all_items = array();
        $new_items = True;
        $page_nr = 1;
        do {
            $new_items = ($this->spotifyCrawler($page_nr)->data->items);
            foreach ($new_items as &$item) {
                // Let's make a match for the field name with the task description
                $item->headline = $item->title;
                // Enrich the object with a description
                $item->description = $this->extractSpotifyJobPostDescription($item->url);
                // Remove the unnecessary information for the task
                unset($item->title);
                unset($item->locations);
                unset($item->categories);
            }
            $this->all_items = array_merge($this->all_items, $new_items);
            $new_count = count($new_items);
            $page_nr++;
            $this->logger->info("Found few more job posts $new_count!");
    
            // Symfony allows better, but for the sake of quick demo,
            // hack for debugging: no of items limit if &debug=true present in URL.
            if (isset($_GET['debug']) and $page_nr > 1) break; 
    
        } while ($new_count > 0);
        return $this->all_items;
    }

    /**
     * POST request method to retrieve the data.
     */
    private function spotifyCrawler(int $pageNr = 1) {
        $url = 'https://www.spotifyjobs.com/wp-admin/admin-ajax.php';
        $data = array(
            'action' => 'get_jobs',
            'pageNr' => $pageNr,
            'perPage' => 16,
            'featuredJobs' => '',
            'category' => 0,
            'location' => 0,
            'search' => '',
            'locations[]' => 'sweden'
        );
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) {
            // TODO: Handle error
        }
        
        $json_data = json_decode($result);
        return $json_data;
    }

    /**
     * Using the regexp iterate through and populate years_required property.
     */
    static public function detectYears(&$jobposts) {
        foreach ($jobposts as &$jobpost) {
            $jobpost->years_required = self::getYears($jobpost->description);
        }
    }

    /**
     * Trying to estimate the expected experience level.
     * I have divided into 3 basic categories:
     *   3 - Senior/Manager
     *   2 - Somehow experienced
     *   1 - Junior/Intern
     *   0 - Unknown
     */
    static function detectExperience(&$jobposts) {
        foreach ($jobposts as &$jobpost) {
            if (self::contains($jobpost->headline, array('senior', 'manager')))
                $level = 3;
            elseif (self::contains($jobpost->description, array('experience', 'experienced')))
                $level = 2;
            elseif (self::contains($jobpost->description, array('junior','internship')))
                $level = 1;
            else
                $level = 0;
            $jobpost->experience_level = $level;
        }
    }

    static public function cleanUp(&$jobposts) {
        foreach ($jobposts as &$jobpost) {
            $jobpost = get_object_vars($jobpost);
        }
    }

    /**
     * Extracts job posting description from a linked page.
     */
    private function extractSpotifyJobPostDescription($url) {
        $client = new Client();
        $this->logger->info("Getting description from $url");
        $crawler = $client->request('GET', $url);
        return $crawler->filter('.column-inner')->html();
    }

    /**
     * Extracts the number of years from the job post description.
     * Manually tested regexp:
     *   Using: https://regex101.com/
     * Regexp:
     *   (([\w\-\+]+)\s+years)
     * Test case:
     *   5 years of previous experience 
     *   2+ years of relevant experience
     *   3-5 years of work experience
     *   few years of people management experience
     *   experience of 3-7+ years
     *   several years of experience
     *   At least 5 years of previous experience from working in the music or entertainment industry
     *   We believe you have approximately 3-5 years of work experience, an impressive portfolio demonstrating design in digital projects and a strong knowledge of Adobe Creative suite (bonus for skills in motion graphics)
     *   You are a manager with a few years of people management experience.
     *   You have 5 years of previous experience 
     *   You worked 2+ years of relevant experience
     *   Has 3-5 years of work experience
     *   Hopefully few years of people management experience
     *   Got experience of 3-7+ years
     *   Present several years of experience
     * 
     * @param string $description   Job post description
     */
    static private function getYears($description)
    {
        // TODO: Hard coded search phrases - should be moved to a config file.
        $search_phrases = array(
            'years of previous experience',
            'years of relevant experience',
            'years of work experience',
            'years of people management experience',
            'years of experience',
            'years',
        );
        foreach ($search_phrases as $search) {
            if (preg_match('(([\w\-\+]+)\s+'.$search.')', $description, $matches)) {
                return $matches[1];
            }
        }
        return '-';
    }   

    static private function contains($str, array $arr)
    {
        foreach($arr as $a) {
            if (stripos($str,$a) !== false) return true;
        }
        return false;
    }

}