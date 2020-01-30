<?php
/**
 * Test file for the crawling service.
 */

namespace App\Tests\Service;

use App\Service\SpotifyHelper;
use PHPUnit\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Test suite.
 */
class SpotifyHelperTest extends TestCase
{
    /**
     * Tests regexp for finding the required years of experience.
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
        $result = [];
        foreach ($testCases as $testCase) {
            $result[] = SpotifyHelper::getYears($testCase);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests a helper method for checking string content.
     */
    public function testContains()
    {
        $result = SpotifyHelper::contains('This is a test case, try me ...', ['This', 'me']);
        $this->assertTrue($result);
        $result = SpotifyHelper::contains('This is a test case, try me ...', ['that', 'these']);
        $this->assertFalse($result);
    }

    /**
     * Test exception of not loaded data.
     */
    public function testNotDataLoaded()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No loaded job posts! Try loadItems() first!');
        $spotifyHelper = new SpotifyHelper();
        $spotifyHelper->getAllItems();
    }

    /**
     * Test job experience level detection.
     */
    public function testDetectExperience()
    {
        $testCases = [
            ['Junior Java Developer', 'We offer unique possibility to join us on an internship. No experience is required!'],
            ['Project Manager', 'Experienced project manager to manage small team of developers.'],
            ['Software Engineer', 'Experienced senior developer with great skills required.'],
            ['HR specialist', 'HR/administration person to work in our office.']
        ];
        $expected = [
            1, 3, 2, 0
        ];
        $result = [];
        foreach ($testCases as $testCase) {
            $result[] = SpotifyHelper::detectExperience($testCase[0], $testCase[1]);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests the job post list reading and description fill, incl. pagination.
     */
    public function testLoadItems()
    {
        $spotifyHelper = new SpotifyHelper();
        $spotifyHelper->loadItems(2);
        $this->assertEquals(32, count($spotifyHelper->getAllItems()));
        foreach ($spotifyHelper->getAllItems() as $item) {
            $this->assertNotEmpty($item['headline']);
            $this->assertNotEmpty($item['description']);
            $this->assertNotEmpty($item['url']);
        }
        // Test years of experience detection and experience levels
        $spotifyHelper->addDetectedYears();
        foreach ($spotifyHelper->getAllItems() as $item) {
            $this->assertNotEmpty($item['yearsRequired']);
        }
        $spotifyHelper->addDetectedExperience();
        foreach ($spotifyHelper->getAllItems() as $item) {
            $this->assertNotEmpty($item['experienceLevel']);
        }
    }
}
