<?php

namespace App\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Service\SpotifyHelper;


class CrawlerController extends AbstractController
{
    /**
     * @Route("/api/crawler/spotify")
     */
    public function spotify(SpotifyHelper $spotifyHelper)
    {
       
        // 1. Create a Crawler
        // Extract:
        // 1. Titles
        // 2. Headlines
        // 3. Descriptions
        $jobposts = $spotifyHelper->run();

        // 2. Detect experience
        $spotifyHelper->detectExperience($jobposts);
        
        // 3. Detect/guess required years of experience
        $spotifyHelper->detectYears($jobposts);

        // Clean-up to make it a nice JSON API
        $spotifyHelper->cleanUp($jobposts);

        return $this->json($jobposts);
    }
}
