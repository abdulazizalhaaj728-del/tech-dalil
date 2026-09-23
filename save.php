<?php
// ملف استقبال وحفظ البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = file_get_contents('php://input');
    if (!empty($inputData)) {
        // التحقق من صحة صيغة الـ JSON قبل الحفظ
        $decoded = json_decode($inputData, true);
        if ($decoded !== null) {
            file_put_contents('data.json', json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(["status" => "success", "message" => "تم الحفظ بنجاح"]);
            exit;
        }
    }
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "بيانات غير صالحة"]);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "طريقة الطلب غير مسموحة"]);
}
?>