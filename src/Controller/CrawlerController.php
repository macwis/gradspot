<?php
/**
 * Crawler controller file
 */

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\SpotifyHelper;

/**
 * Controller class to interface the job crawler
 */
class CrawlerController extends AbstractController
{
    /**
     * Specific endpoint to get jobs from Spotify Sweden.
     *
     * @Route("/api/crawler/spotify")
     */
    public function spotify($demo = false, SpotifyHelper $spotifyHelper)
    {
        // 1. Create a Crawler
        // Extract:
        // 1. Titles
        // 2. Headlines
        // 3. Descriptions
        $jobposts = $spotifyHelper->run($demo == true ? 2 : 0);

        // 2. Detect experience
        $spotifyHelper->detectExperience($jobposts);

        // 3. Detect/guess required years of experience
        $spotifyHelper->detectYears($jobposts);

        // Clean-up to make it a nice JSON API
        $spotifyHelper->cleanUp($jobposts);

        return $this->json($jobposts);
    }
}
