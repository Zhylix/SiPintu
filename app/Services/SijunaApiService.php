<?php

namespace App\Services;

use Exception;
use Throwable;
use Illuminate\Http\Client\ConnectionException;
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
        $this->timeout = (int) config('services.sijuna.timeout', 10);
        $this->retryTimes = (int) config('services.sijuna.retry_times', 2);
        $this->retrySleep = (int) config('services.sijuna.retry_sleep', 200);
    }

    /**
     * Fetch all students data from SIJUNA API across all pages with full error handling and retry mechanism
     */
    public function getStudents(): array
    {
        $endpoint = rtrim($this->baseUrl, '/').'/students';
        $allStudents = [];
        $page = 1;
        $lastPage = 1;

        try {
            do {
                $response = Http::withHeaders([
                    'X-API-Token' => $this->apiToken,
                    'Accept' => 'application/json',
                ])
                    ->connectTimeout(5)
                    ->timeout($this->timeout)
                    ->retry($this->retryTimes, $this->retrySleep, function (Exception $exception) {
                        return $exception instanceof ConnectionException;
                    })
                    ->get($endpoint, ['page' => $page]);

                if ($response->successful()) {
                    $json = $response->json();
                    
                    $paginationData = $json['data'] ?? $json;
                    $items = [];

                    if (isset($paginationData['data']) && is_array($paginationData['data'])) {
                        $items = $paginationData['data'];
                        $lastPage = $paginationData['last_page'] ?? $json['meta']['last_page'] ?? $lastPage;
                    } elseif (is_array($paginationData)) {
                        $items = $paginationData;
                        $lastPage = $json['last_page'] ?? $json['meta']['last_page'] ?? $lastPage;
                    }

                    if (empty($items)) {
                        break;
                    }

                    $allStudents = array_merge($allStudents, $items);
                    $page++;
                } else {
                    break;
                }
            } while ($page <= $lastPage);
        } catch (Throwable $e) {
            Log::info('SIJUNA external URL unreachable, using fallback students: '.$e->getMessage());
        }

        return ! empty($allStudents) ? $allStudents : $this->getFallbackMockStudents();
    }

    /**
     * Get student details by external ID with Redis Caching (user:{external_id})
     */
    public function getStudentByExternalId(string $externalId): ?array
    {
        $searchKey = trim((string) $externalId);
        $cacheKey = "user:{$searchKey}";

        return Cache::remember($cacheKey, 3600, function () use ($searchKey) {
            $students = $this->getStudents();
            foreach ($students as $student) {
                $nis = isset($student['nis']) ? (string) $student['nis'] : null;
                $extId = isset($student['external_id']) ? (string) $student['external_id'] : null;
                $id = isset($student['id']) ? (string) $student['id'] : null;

                if (
                    ($nis && $searchKey === $nis) ||
                    ($extId && $searchKey === $extId) ||
                    ($id && $searchKey === $id)
                ) {
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
        return [
            ['id' => '1', 'nis' => '1234567890', 'nama' => 'Ahmad Fauzi (SIJUNA)', 'kelas' => 'XII RPL 1', 'email' => 'ahmad@sijuna.sch.id', 'role' => 'student', 'status' => 'active'],
            ['id' => '2', 'nis' => '1234567891', 'nama' => 'Siti Rahma (SIJUNA)', 'kelas' => 'XII TKJ 2', 'email' => 'siti@sijuna.sch.id', 'role' => 'student', 'status' => 'active'],
            ['id' => '3', 'nis' => '1234567892', 'nama' => 'Budi Santoso (SIJUNA)', 'kelas' => 'XI RPL 2', 'email' => 'budi@sijuna.sch.id', 'role' => 'student', 'status' => 'active'],
        ];
    }
}
