<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ImportRateLimiter
{
    public function __construct(
        #[Autowire(service: 'app.rate_limiter.api_import_start')]
        private RateLimiterFactory $apiImportStartLimiter,
        #[Autowire(service: 'app.rate_limiter.api_import_status')]
        private RateLimiterFactory $apiImportStatusLimiter,
        #[Autowire(service: 'app.rate_limiter.web_upload')]
        private RateLimiterFactory $webUploadLimiter,
        #[Autowire('%kernel.environment%')]
        private string $appEnv,
    ) {
    }

    /**
     * @return array{allowed: bool, limit: int, remaining: int, retry_after: int}
     */
    public function consumeApiImportStart(?string $clientIdentifier): array
    {
        return $this->consume($this->apiImportStartLimiter, $clientIdentifier);
    }

    /**
     * @return array{allowed: bool, limit: int, remaining: int, retry_after: int}
     */
    public function consumeApiImportStatus(?string $clientIdentifier): array
    {
        return $this->consume($this->apiImportStatusLimiter, $clientIdentifier);
    }

    /**
     * @return array{allowed: bool, limit: int, remaining: int, retry_after: int}
     */
    public function consumeWebUpload(?string $clientIdentifier): array
    {
        return $this->consume($this->webUploadLimiter, $clientIdentifier);
    }

    /**
     * @return array{allowed: bool, limit: int, remaining: int, retry_after: int}
     */
    private function consume(RateLimiterFactory $factory, ?string $clientIdentifier): array
    {
        if ($this->appEnv === 'test' || defined('PHPUNIT_COMPOSER_INSTALL')) {
            return [
                'allowed' => true,
                'limit' => 0,
                'remaining' => 0,
                'retry_after' => 0,
            ];
        }

        $identifier = $clientIdentifier !== null && $clientIdentifier !== '' ? $clientIdentifier : 'anonymous';
        $limit = $factory->create($identifier)->consume();
        $retryAfter = $limit->getRetryAfter();

        return [
            'allowed' => $limit->isAccepted(),
            'limit' => $limit->getLimit(),
            'remaining' => $limit->getRemainingTokens(),
            'retry_after' => max(0, $retryAfter->getTimestamp() - time()),
        ];
    }
}
