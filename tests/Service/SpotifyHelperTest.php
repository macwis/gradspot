<?php
/**
 * Test file for the crawling service.
 */

namespace App\Tests\Service;

use App\Service\SpotifyHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test suite
 */
class SpotifyHelperTest extends TestCase
{
    /**
     * Tests regexp for finding the required years of experience
     */
    public function testGetYears()
    {
        $testCases = [
            'have 5 years of previous experience',
            'have 2+ years of relevant experience',
            'have 3-5 years of work experience',
            'few years of people management experience',
            'experience of 3-7+ years',
            'several years of experience',
            'At least 5 years of previous experience from working in the music or entertainment industry',
            'We believe you have approximately 3-5 years of work experience, an impressive portfolio demonstrating design in digital projects and a strong knowledge of Adobe Creative suite (bonus for skills in motion graphics)',
            'You are a manager with a few years of people management experience.',
            'You have 5 years of previous experience',
            'You worked 2+ years of relevant experience',
            'Has 3-5 years of work experience',
            'Hopefully few years of people management experience',
            'Got experience of 3-7+ years',
            'Present several years of experience.',
        ];
        $sh = new SpotifyHelper();
        $result = [];
        foreach ($testCases as $testCase) {
            $result[] = $sh->getYears($testCase);
        }
        $expected = [
            '5',
            '2+',
            '3-5',
            'few',
            '3-7+',
            'several',
            '5',
            '3-5',
            'few',
            '5',
            '2+',
            '3-5',
            'few',
            '3-7+',
            'several',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests a helper method for checking string content
     */
    public function testContains()
    {
        $sh = new SpotifyHelper();
        $result = $sh->contains("This is a test case, try me ...", ['This', 'me']);
        $this->assertTrue($result);
        $result = $sh->contains("This is a test case, try me ...", ['that', 'these']);
        $this->assertFalse($result);
    }

    /**
     * Tests the workflow for crawling just the first two pages
     */
    public function testRun()
    {
        $sh = new SpotifyHelper();
        $allItems = $sh->run(2);
        $this->assertEquals(32, count($allItems));
        foreach ($allItems as $item) {
            $this->assertNotEmpty($item->headline);
            $this->assertNotEmpty($item->description);
            $this->assertNotEmpty($item->url);
        }
    }
}
