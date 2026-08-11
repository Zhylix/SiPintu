<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            ['id' => '1', 'nis' => '1234567890', 'nama' => 'Ahmad Fauzi (SIJUNA)', 'classroom' => 'XII PPLG 1', 'kelas' => 'XII PPLG 1', 'email' => 'ahmad@sijuna.sch.id', 'status' => 'active'],
            ['id' => '2', 'nis' => '1234567891', 'nama' => 'Siti Rahma (SIJUNA)', 'classroom' => 'XII MPLB 2', 'kelas' => 'XII MPLB 2', 'email' => 'siti@sijuna.sch.id', 'status' => 'active'],
            ['id' => '3', 'nis' => '1234567892', 'nama' => 'Budi Santoso (SIJUNA)', 'classroom' => 'XI AKL 2', 'kelas' => 'XI AKL 2', 'email' => 'budi@sijuna.sch.id', 'status' => 'active'],
            ['id' => '4', 'nis' => '1234567893', 'nama' => 'Rian Ardianto (Alumni RPL)', 'classroom' => null, 'kelas' => null, 'email' => 'rian.alumni@sijuna.sch.id', 'status' => 'active'],
            ['id' => '5', 'nis' => '1234567894', 'nama' => 'Dwi Handayani (Alumni TKJ)', 'classroom' => null, 'kelas' => null, 'email' => 'dwi.alumni@sijuna.sch.id', 'status' => 'active'],
        ];
    }

    /**
     * Fetch all teachers data from SIJUNA API (https://sijuna.com/api/guru)
     */
    public function getTeachers(): array
    {
        // Check direct URL https://sijuna.com/api/guru or relative /guru
        $endpoint = str_contains($this->baseUrl, 'sijuna.com') ? 'https://sijuna.com/api/guru' : rtrim($this->baseUrl, '/').'/guru';
        $allTeachers = [];
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

                    $allTeachers = array_merge($allTeachers, $items);
                    $page++;
                } else {
                    break;
                }
            } while ($page <= $lastPage);
        } catch (Throwable $e) {
            Log::info('SIJUNA external teacher URL unreachable, using fallback teachers: '.$e->getMessage());
        }

        return ! empty($allTeachers) ? $allTeachers : $this->getFallbackMockTeachers();
    }

    /**
     * Get teacher details by external ID, NIP, or email with Redis Caching (teacher:{key})
     */
    public function getTeacherByExternalId(string $identifier): ?array
    {
        $searchKey = trim((string) $identifier);
        $cacheKey = "teacher:{$searchKey}";

        return Cache::remember($cacheKey, 3600, function () use ($searchKey) {
            $teachers = $this->getTeachers();
            foreach ($teachers as $teacher) {
                $nip = isset($teacher['nip']) ? (string) $teacher['nip'] : null;
                $extId = isset($teacher['external_id']) ? (string) $teacher['external_id'] : null;
                $id = isset($teacher['id']) ? (string) $teacher['id'] : null;
                $email = isset($teacher['email']) ? (string) $teacher['email'] : null;
                $username = isset($teacher['username']) ? (string) $teacher['username'] : null;

                if (
                    ($email && strcasecmp($searchKey, $email) === 0) ||
                    ($nip && $searchKey === $nip) ||
                    ($extId && $searchKey === $extId) ||
                    ($id && $searchKey === $id) ||
                    ($username && strcasecmp($searchKey, $username) === 0)
                ) {
                    return $teacher;
                }
            }

            return null;
        });
    }

    /**
     * Fallback mock teacher data when SIJUNA external API server is offline or unreachable
     */
    protected function getFallbackMockTeachers(): array
    {
        return [
            ['id' => '101', 'nip' => '198501012010011001', 'nama' => 'Bambang S.Pd (SIJUNA)', 'email' => 'bambang@sijuna.sch.id', 'phone' => '081234567890', 'role' => 'teacher', 'status' => 'active'],
            ['id' => '102', 'nip' => '199002022015022002', 'nama' => 'Siti Nurhaliza M.Pd (SIJUNA)', 'email' => 'siti.guru@sijuna.sch.id', 'phone' => '081234567891', 'role' => 'teacher', 'status' => 'active'],
            ['id' => '103', 'nip' => '199203032018031003', 'nama' => 'Drs. Agus Wijaya (SIJUNA)', 'email' => 'agus.guru@sijuna.sch.id', 'phone' => '081234567892', 'role' => 'teacher', 'status' => 'active'],
        ];
    }
}
