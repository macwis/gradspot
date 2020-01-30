<?php
/**
 * Test file for the crawling controller.
 */

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

function isJson($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * Test suite.
 */
class PostControllerTest extends WebTestCase
{
    /**
     * Tests controller response.
     */
    public function testCrawlSpotify()
    {
        $client = static::createClient();
        $client->request('GET', '/api/crawl/spotify?demo=true');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
