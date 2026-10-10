<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiRosterService
{
    /**
     * Store uploaded roster image and parse student list via Gemini Vision OCR.
     */
    public function scanRosterImage(UploadedFile $file, ?string $clientApiKey = null): array
    {
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs('roster_uploads', $fileName, 'public');

        $geminiKey = $clientApiKey ?: env('GEMINI_API_KEY');
        $parsedStudents = [];

        if (!empty($geminiKey)) {
            $parsedStudents = $this->callGeminiVision($file, $geminiKey);
        }

        return [
            'image_path' => Storage::url($path),
            'students' => $parsedStudents,
            'total_detected' => count($parsedStudents),
        ];
    }

    /**
     * Call Gemini API to extract roster JSON from image using modern Gemini Vision models.
     */
    private function callGeminiVision(UploadedFile $file, string $apiKey): array
    {
        try {
            $imageData = base64_encode(file_get_contents($file->getRealPath()));
            $mimeType = $file->getMimeType() ?: 'image/jpeg';

            $prompt = 'คุณเป็นระบบ AI OCR สกัดข้อมูลรายชื่อนักเรียนไทยจากภาพถ่ายเอกสารหรือตารางรายชื่อ ' .
                'สกัดรายชื่อนักเรียนทุกคนที่ปรากฏในภาพ และส่งกลับเฉพาะ JSON Array เท่านั้น: ' .
                '[{"student_number": 1, "student_code": "10001", "title": "นาย/นางสาว/ด.ช./ด.ญ.", "first_name": "ชื่อ", "last_name": "นามสกุล", "gender": "male หรือ female"}] ' .
                'ข้อกำหนดสำคัญ: ' .
                '1. สกัดเฉพาะรายชื่อที่มีอยู่ในภาพจริงเท่านั้น ห้ามเดาหรือสร้างรายชื่อขึ้นมาเอง ' .
                '2. เลขที่: ใช้เลขที่ในเอกสาร หากไม่มีให้เรียง 1, 2, 3... ' .
                '3. รหัสนักเรียน: สกัดรหัสประจำตัวหากมี หากไม่มีให้สร้างรหัสชั่วคราวตามลำดับเลขที่ ' .
                '4. คำนำหน้า: ด.ช. / ด.ญ. / นาย / นางสาว / เด็กชาย / เด็กหญิง (แยกออกจากชื่อจริง) ' .
                '5. เพศ: คำนำหน้า ด.ช./นาย/เด็กชาย ให้ใส่ male และ ด.ญ./นางสาว/เด็กหญิง/นาง ให้ใส่ female ' .
                '6. ตอบเฉพาะ JSON Array บริสุทธิ์เท่านั้น ห้ามมี markdown หรือข้อความอธิบายอื่นใด';

            $modelsToTry = [
                'gemini-flash-lite-latest',
                'gemini-3.5-flash-lite',
                'gemini-3.1-flash-lite',
                'gemini-3.7-flash',
                'gemini-3.8-flash',
            ];

            foreach ($modelsToTry as $model) {
                $response = Http::timeout(45)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                    [
                                        'inlineData' => [
                                            'mimeType' => $mimeType,
                                            'data' => $imageData,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                );

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if ($text) {
                        $cleaned = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
                        $json = json_decode($cleaned, true);
                        if (is_array($json)) {
                            // Filter valid students having at least a name
                            $valid = array_values(array_filter($json, function ($item) {
                                return is_array($item) && (!empty($item['first_name']) || !empty($item['name']));
                            }));
                            if (count($valid) > 0) {
                                return $valid;
                            }
                        }
                    }
                } else {
                    Log::warning("Gemini Vision model {$model} returned {$response->status()}: " . $response->body());
                }
            }
        } catch (\Throwable $e) {
            Log::error('Gemini OCR error: ' . $e->getMessage());
        }

        return [];
    }
}
