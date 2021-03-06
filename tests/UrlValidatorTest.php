<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\SafeCurl\Tests;

use Garden\SafeCurl\Exception\InvalidURLException;
use Garden\SafeCurl\UrlPartsList;
use Garden\SafeCurl\UrlValidator;
use PHPUnit\Framework\TestCase;

/**
 * Verify functionality in the URL validator class.
 */
class UrlValidatorTest extends TestCase {

    /**
     * Data for testing basic URL validation.
     *
     * @return array
     */
    public function dataForValidate(): array {
        return [
            [
                "http://user@:80",
                InvalidURLException::class,
                "Unable to parse URL.",
            ],
            [
                "http:///www.example.com/",
                InvalidURLException::class,
                "Unable to parse URL.",
            ],
            [
                "http://:80",
                InvalidURLException::class,
                "Unable to parse URL.",
            ],
            [
                "/nohost",
                InvalidURLException::class,
                "No host found in URL.",
            ],
            [
                "ftp://www.example.com",
                InvalidURLException::class,
                "Scheme is not whitelisted.",
            ],
            [
                "http://www.example.com:22",
                InvalidURLException::class,
                "Port is not whitelisted.",
            ],
            [
                "http://login:password@www.example.com:80",
                InvalidURLException::class,
                "Credentials not allowed as part of the URL.",
            ],
        ];
    }

    /**
     * Verify basic URL validation.
     *
     * @param string $url
     * @param string $exception
     * @param string $message
     * @dataProvider dataForValidate
     */
    public function testValidateUrl(string $url, string $exception, string $message) {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $urlValidator = new UrlValidator();
        $urlValidator->validateUrl($url);
    }

    /**
     * Verify scheme blacklisting.
     */
    public function testValidateScheme() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Scheme is blacklisted.");

        $blacklist = new UrlPartsList();
        $blacklist->addScheme("http");
        $urlValidator = new UrlValidator($blacklist);

        $urlValidator->validateUrl("http://www.example.com");
    }

    /**
     * Verify port blacklisting.
     */
    public function testValidatePort() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Port is blacklisted.");

        $blacklist = new UrlPartsList();
        $blacklist->addPort(8080);
        $urlValidator = new UrlValidator($blacklist);

        $urlValidator->validateUrl("http://www.example.com:8080");
    }

    /**
     * Verify host blacklisting using regex pattern matching.
     */
    public function testValidateHostBlacklist() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Host is blacklisted.");

        $blacklist = new UrlPartsList();
        $blacklist->addHost("(.*)\.example\.com");
        $urlValidator = new UrlValidator($blacklist);

        $urlValidator->validateUrl("http://www.example.com");
    }

    /**
     * Verify host whitelisting using regex pattern matching.
     */
    public function testValidateHostWhitelist() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Host is not whitelisted.");

        $whitelist = new UrlPartsList();
        $whitelist->addHost("(.*)\.vanillaforums\.com");
        $urlValidator = new UrlValidator(null, $whitelist);

        $urlValidator->validateUrl("http://www.example.com");
    }

    /**
     * Verify inability to resolve a hostname generates an error.
     */
    public function testValidateHostWithnoip() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Unable to resolve host.");

        $urlValidator = new UrlValidator();
        $urlValidator->validateUrl("http://www.example.invalid");
    }

    /**
     * Verify IP address blocking using whitelisting.
     */
    public function testValidateHostWithWhitelistIp() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Host does not resolve to a whitelisted address.");

        $whitelist = new UrlPartsList();
        $whitelist->addIP("1.1.1.1");
        $urlValidator = new UrlValidator(null, $whitelist);

        $urlValidator->validateUrl("http://2.2.2.2");
    }

    /**
     * Verify whitelisted IP addresses are valid.
     */
    public function testValidateHostWithWhitelistIpOk() {
        $whitelist = new UrlPartsList();
        $whitelist->addIP("1.1.1.1");
        $urlValidator = new UrlValidator(null, $whitelist);

        $result = $urlValidator->validateUrl("http://1.1.1.1");

        $this->assertCount(3, $result);
        $this->assertArrayHasKey("url", $result);
        $this->assertArrayHasKey("host", $result);
        $this->assertArrayHasKey("ips", $result);
        $this->assertArrayHasKey(0, $result["ips"]);
    }

    /**
     * Verify IP address blocking using blacklisting.
     */
    public function testValidateHostWithBlacklistIp() {
        $this->expectException(InvalidURLException::class);
        $this->expectExceptionMessage("Host resolves to a blacklisted address.");

        $blacklist = new UrlPartsList();
        $blacklist->addIP("1.1.1.1");
        $urlValidator = new UrlValidator($blacklist);

        $urlValidator->validateUrl("http://1.1.1.1");
    }

    /**
     * Verify defaults are adequate for typical URLs.
     */
    public function testValidateUrlOk() {
        $urlValidator = new UrlValidator();
        $result = $urlValidator->validateUrl("http://www.example.com:8080");

        $this->assertCount(3, $result);
        $this->assertArrayHasKey("url", $result);
        $this->assertArrayHasKey("host", $result);
        $this->assertArrayHasKey("ips", $result);
        $this->assertArrayHasKey(0, $result["ips"]);
        $this->assertEquals("http://www.example.com:8080", $result["url"]);
        $this->assertEquals("www.example.com", $result["host"]);
    }
}
