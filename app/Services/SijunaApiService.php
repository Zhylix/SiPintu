<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SijunaApiService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;

    public function __construct()
    {
        $this->baseUrl = config('services.sijuna.url', 'https://sijuna.com/api/external');
        $this->apiToken = config('services.sijuna.token', 'TOKEN_RAHASIA');
        $this->timeout = config('services.sijuna.timeout', 10);
        $this->retryTimes = config('services.sijuna.retry_times', 3);
        $this->retrySleep = config('services.sijuna.retry_sleep', 200);
    }

    /**
     * Fetch students data from SIJUNA API with full error handling and retry mechanism
     *
     * @return array
     * @throws Exception
     */
    public function getStudents(): array
    {
        $endpoint = rtrim($this->baseUrl, '/') . '/students';
        $allStudents = [];
        $page = 1;
        $lastPage = 1;

        try {
            do {
                $response = Http::withHeaders([
                    'X-API-Token' => $this->apiToken,
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleep, function (Exception $exception) {
                    return $exception instanceof ConnectionException;
                })
                ->get($endpoint, ['page' => $page]);

                if ($response->successful()) {
                    $json = $response->json();
                    $paginationData = $json['data'] ?? [];
                    
                    $pageItems = $paginationData['data'] ?? [];
                    $allStudents = array_merge($allStudents, $pageItems);

                    $lastPage = $paginationData['last_page'] ?? $page;
                    $page++;
                } else {
                    $status = $response->status();
                    Log::warning("SIJUNA API returned HTTP status {$status} on page {$page}.");
                    break;
                }
            } while ($page <= $lastPage);

            return $allStudents;
        } catch (ConnectionException $e) {
            Log::warning("SIJUNA API Connection Failed: " . $e->getMessage());
            return $allStudents ?: $this->getFallbackMockStudents();
        } catch (Exception $e) {
            Log::warning("SIJUNA API Service Exception: " . $e->getMessage());
            return $allStudents ?: $this->getFallbackMockStudents();
        }
    }

    /**
     * Get student details by external ID with Redis Caching (user:{external_id})
     */
    public function getStudentByExternalId(string $externalId): ?array
    {
        $cacheKey = "user:{$externalId}";

        return Cache::remember($cacheKey, 3600, function () use ($externalId) {
            $students = $this->getStudents();
            foreach ($students as $student) {
                if ((string) ($student['external_id'] ?? $student['id'] ?? '') === (string) $externalId) {
                    return $student;
                }
            }
            return null;
        });
    }

    /**
     * Fallback mock student data when SIJUNA external API server is offline or unreachable
     */
    protected function getFallbackMockStudents(): array
    {
        return [];
    }
}
