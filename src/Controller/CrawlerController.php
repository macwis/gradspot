<?php
/**
 * Crawler controller file.
 */

namespace App\Controller;

use App\Service\SpotifyHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller class to interface the job crawler.
 */
class CrawlerController extends AbstractController
{
    /**
     * Specific endpoint to get jobs from Spotify Sweden.
     *
     * @Route("/api/crawl/spotify")
     */
    public function crawlSpotify(SpotifyHelper $spotifyHelper, Request $request)
    {
        // 1. Create a Crawler
        // Extract:
        // 1. Titles
        // 2. Headlines
        // 3. Descriptions
        $demo = $request->query->get('demo') != null ? true : false;
        $spotifyHelper->loadItems($demo ? 1 : 0);

        // 2. Detect experience
        $spotifyHelper->addDetectedExperience();

        // 3. Detect/guess required years of experience
        $spotifyHelper->addDetectedYears();

        return $this->json($spotifyHelper->getAllItems());
    }
}
