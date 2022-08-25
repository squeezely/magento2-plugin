<?php
/**
 * Copyright © Squeezely B.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Squeezely\Plugin\Model\Api;

use Exception;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Class CurlExtra
 *
 * Provide DEL method
 */
class CurlExtra extends Curl
{

    /**
     * Make DEL request using curl
     * Magento has no built in support for 'delete'
     *
     * @param string $uri
     *
     * @return void
     */
    public function del($uri, $params)
    {
        $this->makeDeleteRequest($uri, $params);
    }

    /**
     * @param string $uri
     * @param string|array $params
     * @throws Exception
     */
    protected function makeDeleteRequest($uri, $params)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $this->_ch = curl_init();
        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);
        $this->curlOption(CURLOPT_URL, $uri);
        $this->curlOption(CURLOPT_POSTFIELDS, is_array($params) ? http_build_query($params) : $params);
        $this->curlOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->curlOption(CURLOPT_RETURNTRANSFER, true);

        if (count($this->_headers)) {
            $heads = [];
            foreach ($this->_headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }

        if (count($this->_cookies)) {
            $cookies = [];
            foreach ($this->_cookies as $k => $v) {
                $cookies[] = "{$k}={$v}";
            }
            $this->curlOption(CURLOPT_COOKIE, implode(";", $cookies));
        }

        if ($this->_timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
        }

        if ($this->_port != 80) {
            $this->curlOption(CURLOPT_PORT, $this->_port);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlOption(CURLOPT_HEADERFUNCTION, [$this, 'parseHeaders']);

        if (count($this->_curlUserOptions)) {
            foreach ($this->_curlUserOptions as $k => $v) {
                $this->curlOption($k, $v);
            }
        }

        $this->_headerCount = 0;
        $this->_responseHeaders = [];

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $this->_responseBody = curl_exec($this->_ch);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $err = curl_errno($this->_ch);
        if ($err) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $this->doError(curl_error($this->_ch));
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        curl_close($this->_ch);
    }
}
