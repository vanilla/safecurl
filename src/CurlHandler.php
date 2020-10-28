<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\SafeCurl;


class CurlHandler
{
    /**
     * @var resource
     */
    private $curlResource;

    public function __construct($curlResource) {
        $this->curlResource = $curlResource;
    }

    public function execute() {
        return curl_exec($this->curlResource);
    }

    public function getError(): string {
        return curl_error($this->curlResource);
    }

    public function getErrorNumber(): int {
        return curl_errno($this->curlResource);
    }

    public function getInfo(int $option = null) {
        return curl_getinfo($this->curlResource, $option);
    }

    public function getVersion(): array {
        return curl_version();
    }

    public function setOption($option, $value): void {
        curl_setopt($this->curlResource, $option, $value);
    }
}
