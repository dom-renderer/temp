<?php

namespace App\Helpers;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\Payroll;
use App\Models\Rental;
use App\Models\Task;

class Helper {

    public static $roles = [
        'admin' => 1,
        'driver' => 2,
        'second-man' => 3,
        'customer' => 4,
        'office-manager' => 5,
    ];

    public static $removalStatuses = [
        'Ordered',
        'Pending',
        'Cancelled',
        'On Hold',
        'In Progress',
        'Completed',
    ];

    public static $removalStatusesId = [
        'ordered' => 0,
        'pending' => 1,
        'cancelled' => 2,
        'on-hold' => 3,
        'in-progress' => 4,
        'completed' => 5,
    ];

    public static $errorMessage = 'Oops! Something went wrong.';

    public static $emptyValueDataTable = 'N/A';

    public static function slug($string, $separator = '-') {
        $string = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $string);
        $string = trim($string, $separator);
        if (function_exists('mb_strtolower')) {
            $string = mb_strtolower($string);
        } else {
            $string = strtolower($string);
        }
        $string = preg_replace("/[\/_|+ -]+/", $separator, $string);

        return $string;
    }

    public static function sendPushNotification($device_ids, $data) {

        $keyFilePath = storage_path('app/firebase-fcm.json');
        
        $client = new \Google\Client();
        $client->setAuthConfig($keyFilePath);
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
    
        $tokenArray = $client->fetchAccessTokenWithAssertion();
        
        if (isset($tokenArray['error'])) {
            return false;
        }
    
        $accessToken = $tokenArray['access_token'];


        foreach ($device_ids as $did) {
            $notification = json_encode([
                "message" => [
                    "token" => $did, 
                    "notification" => [
                        "body" => $data['description'],
                        "title" => $data['title'],
                    ],
                    "android" => [
                        "priority" => "HIGH",
                    ],
                ]
            ]);
            
            $headers = array(
                'Authorization: Bearer '.$accessToken,
                'Content-Type: application/json'
            );
    
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/skip-app/messages:send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $notification);
    
            curl_exec($ch);
        }

        return true;
    }

    public static function generateCode($string = '', $eloquent = ['model' => 'User', 'column' => 'code']) {
        $string = strtoupper($string);
        $finalCode = strtoupper(\Str::random(3)) . rand(1000, 9999);

        $model = $eloquent['model'];
        $column = $eloquent['column'];

        if (is_string($string)) {
            if (strlen($string) >= 3) {
                $finalCode = mb_substr($string, 0, 3) . rand(1000, 9999);
            } else if (strlen($string) >= 1) {
                $finalCode = mb_substr($string, 0,  strlen($string)) . rand( \Str::padRight('', (7 - strlen($string)), 1) , \Str::padRight('', (7 - strlen($string)), 9));
            }
        }

        if (resolve("App\Models\\$model")::where(\DB::raw("UPPER($column)"), $finalCode)->exists()) {
            return self::generateCode($string);
        } 

        return $finalCode;
    }

    public static function generateRentalNumber () {
        $orderNo = 0;
        
        if (Rental::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $orderNo = Rental::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $orderNo += 1;
        $orderNo = sprintf('%07d', $orderNo);
        $orderNo = "{$orderNo}";

        return $orderNo;
    }

    public static function generateTaskNumber () {
        $taskNo = 0;
        
        if (Task::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $taskNo = Task::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $taskNo += 1;
        $taskNo = sprintf('%07d', $taskNo);
        $taskNo = "{$taskNo}";

        return $taskNo;
    }

    public static function generateInvoiceNumber () {
        $taskNo = 0;
        
        if (Invoice::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $taskNo = Invoice::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $taskNo += 1;
        $taskNo = sprintf('%07d', $taskNo);
        $taskNo = "INV-{$taskNo}";

        return $taskNo;
    }

    public static function regenerateInvoiceNumber ($number) {
        try {
            $exploded = explode('-', $number);

            if (count($exploded) <= 2) {
                $number .= '-1';
            } else {
                $number = $exploded[0] . '-' . $exploded[1] . '-' . strval(intval($exploded[2]) + 1);
            }
        } catch (\Exception $e) {
            $number = self::generateInvoiceNumber();
        }

        return $number;
    }    


    public static function generateCreditNoteNumber () {
        $taskNo = 0;
        
        if (CreditNote::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $taskNo = CreditNote::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $taskNo += 1;
        $taskNo = sprintf('%07d', $taskNo);
        $taskNo = "CRN-{$taskNo}";

        return $taskNo;
    }

    public static function generatePayrollNumber () {
        $payrollNo = 0;
        
        if (Payroll::withTrashed()->orderBy('id', 'DESC')->first() !== null) {
            $payrollNo = Payroll::withTrashed()->orderBy('id', 'DESC')->first()->id;
        }

        $payrollNo += 1;
        $payrollNo = sprintf('%07d', $payrollNo);
        $payrollNo = "PAY-{$payrollNo}";

        return $payrollNo;
    }

    public static function generateQR($code, $fileName) {
        $thisUuid = uniqid();
        $tempFilePath = storage_path("app/public/skip-qr/{$thisUuid}.png");

        \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
        ->size(300)
        ->generate($code, $tempFilePath);

        $qrImage = \Intervention\Image\Facades\Image::make($tempFilePath);

        $fontSize = 20;
        $textMargin = 10;
    
        $width = $qrImage->getWidth();
        $height = $qrImage->getHeight() + $fontSize + $textMargin * 2;
    
        $canvas = \Intervention\Image\Facades\Image::canvas($width, $height, '#FFFFFF');
    
        $canvas->insert($qrImage, 'top');
    
        $canvas->text($code, $width / 2, $qrImage->getHeight() + $textMargin + ($fontSize / 2), function($font) use ($fontSize) {
            $font->size($fontSize);
            $font->color('#000000');
            $font->align('center');
            $font->valign('bottom');
        });
    
        $filePath = "public/skip-qr/{$fileName}.png";
        \Storage::put($filePath, (string) $canvas->encode('png'));
    
        @unlink($tempFilePath);

        /* QR Without Inventory Code */
        $thisUuid = uniqid();
        $tempFilePath = storage_path("app/public/skip-qr/{$thisUuid}_wcode.png");

        \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
        ->size(300)
        ->generate($code, $tempFilePath);

        $qrImage = \Intervention\Image\Facades\Image::make($tempFilePath);

        $fontSize = 20;
        $textMargin = 10;
    
        $width = $qrImage->getWidth();
        $height = $qrImage->getHeight() + $fontSize + $textMargin * 2;
    
        $canvas = \Intervention\Image\Facades\Image::canvas($width, $height, '#FFFFFF');
    
        $canvas->insert($qrImage, 'top');
    
        $filePath2 = "public/skip-qr/{$fileName}_wcode.png";
        \Storage::put($filePath2, (string) $canvas->encode('png'));
    
        @unlink($tempFilePath);        
        /* QR Without Inventory Code */


        return storage_path($filePath);
    }

    public static function isBase64($string) {
        if (empty($string) || strlen($string) < 4) {
            return false;
        }
    
        $decoded = base64_decode($string, true);
        return base64_encode($decoded) === $string;
    }

    public static function getBase64Extension($base64String) {
        $matches = [];
        preg_match("/data:image\/(.*);base64/", $base64String, $matches);
        return $matches[1] ?? 'png';
    }

    public static function downloadBase64File($base64String, $title, $path)
    {        
        $extension = self::getBase64Extension($base64String);

        $fileData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64String));
        $filename = "{$title}.{$extension}";
        $filePath = "{$path}/{$filename}";

        file_put_contents($filePath, $fileData);

        return $filename;
    }
    
    public static function formatNumber($number, $precision = 1) {
        if ($number < 900) {
            $formatted = number_format($number, $precision);
        } elseif ($number < 900000) {
            $formatted = number_format($number / 1000, $precision) . 'K';
        } elseif ($number < 900000000) {
            $formatted = number_format($number / 1000000, $precision) . 'M';
        } else {
            $formatted = number_format($number / 1000000000, $precision) . 'B';
        }
    
        return str_replace('.0', '', $formatted);
    }

    public static function incrementAlphaNumeric($input)
    {
        if (preg_match('/(.*?)(\d+)$/', $input, $matches)) {
            $prefix = $matches[1];
            $number = $matches[2];

            $incremented = str_pad((int)$number + 1, strlen($number), '0', STR_PAD_LEFT);
            return $prefix . $incremented;
        }

        return strval(time());
    }

    public static function number_format($number, int $decimals = 2)
    {
        $number = (float) $number;
        return sprintf('%.' . $decimals . 'f', $number);
    }

    public static function minDate(string ...$dates)
    {
        if (empty($dates)) {
            return date('Y-m-d H:i:s');
        }

        $validDates = array_filter($dates, function ($date) {
            $d = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
            return $d && $d->format('Y-m-d H:i:s') === $date;
        });

        if (empty($validDates)) {
            return date('Y-m-d H:i:s');
        }

        usort($validDates, function ($a, $b) {
            return strtotime($a) <=> strtotime($b);
        });

        return $validDates[0];
    }

    public static function maxDate(string ...$dates)
    {
        if (empty($dates)) {
            return date('Y-m-d H:i:s');
        }

        $validDates = array_filter($dates, function ($date) {
            $d = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
            return $d && $d->format('Y-m-d H:i:s') === $date;
        });

        if (empty($validDates)) {
            return date('Y-m-d H:i:s');
        }

        usort($validDates, function ($a, $b) {
            return strtotime($a) <=> strtotime($b);
        });

        return end($validDates);
    }

}
