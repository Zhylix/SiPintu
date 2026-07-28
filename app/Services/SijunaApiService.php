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

        try {
            $response = Http::withHeaders([
                'X-API-Token' => $this->apiToken,
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, function (Exception $exception) {
                return $exception instanceof ConnectionException;
            })
            ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                
                return $data['data']['data'] ?? [];
            }

            // Handle specific status codes
            $status = $response->status();
            Log::warning("SIJUNA API returned HTTP status {$status}. Using local mock data fallback.");
            return $this->getFallbackMockStudents();
        } catch (ConnectionException $e) {
            Log::warning("SIJUNA API Connection Failed: " . $e->getMessage());
            return $this->getFallbackMockStudents();
        } catch (Exception $e) {
            Log::warning("SIJUNA API Service Exception: " . $e->getMessage() . ". Using fallback data.");
            return $this->getFallbackMockStudents();
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
        return [
            [
                'external_id' => 'SIJ-STUDENT-001',
                'nisn' => '0051234567',
                'name' => 'Ahmad Rizky Pratama',
                'email' => 'ahmad.rizky@siswa.sekolah.id',
                'phone' => '081234567890',
                'class' => 'XII RPL 1',
                'major' => 'Rekayasa Perangkat Lunak',
                'status' => 'active',
            ],
            [
                'external_id' => 'SIJ-STUDENT-002',
                'nisn' => '0051234568',
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@siswa.sekolah.id',
                'phone' => '081234567891',
                'class' => 'XII RPL 1',
                'major' => 'Rekayasa Perangkat Lunak',
                'status' => 'active',
            ],
            [
                'external_id' => 'SIJ-STUDENT-003',
                'nisn' => '0051234569',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@siswa.sekolah.id',
                'phone' => '081234567892',
                'class' => 'XII TKJ 2',
                'major' => 'Teknik Komputer & Jaringan',
                'status' => 'active',
            ],
            [
                'external_id' => 'SIJ-STUDENT-004',
                'nisn' => '0051234570',
                'name' => 'Dewi Anggraini',
                'email' => 'dewi.anggraini@siswa.sekolah.id',
                'phone' => '081234567893',
                'class' => 'XII DKV 1',
                'major' => 'Desain Komunikasi Visual',
                'status' => 'active',
            ],
            [
                'external_id' => 'SIJ-STUDENT-005',
                'nisn' => '0051234571',
                'name' => 'Eko Wijaya',
                'email' => 'eko.wijaya@siswa.sekolah.id',
                'phone' => '081234567894',
                'class' => 'XII MM 2',
                'major' => 'Multimedia',
                'status' => 'active',
            ],
        ];
    }
}
