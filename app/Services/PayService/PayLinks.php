<?php

namespace App\Services\PayService;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class PayLinks
 * @package App\Services\PayService
 */
class PayLinks implements Arrayable
{
    /**
     * @var string
     */
    protected string $successUrl;

    /**
     * @var string
     */
    protected string $failUrl;

    /**
     * PayLinks constructor.
     * @param string $successUrl
     * @param string $failUrl
     */
    public function __construct(string $successUrl, string $failUrl)
    {
        $this->successUrl = $successUrl;
        $this->failUrl = $failUrl;
    }

    /**
     * @return string
     */
    public function getFailUrl(): string
    {
        return $this->failUrl;
    }

    /**
     * @return string
     */
    public function getSuccessUrl(): string
    {
        return $this->successUrl;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'fail' => $this->failUrl,
            'success' => $this->successUrl,
        ];
    }
}
