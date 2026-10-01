<?php
// إعداد رأس الصفحة ليكون بصيغة JSON وتحديد ترميز الأحرف
header('Content-Type: application/json; charset=UTF-8');

// التأكد من أن الطلب يتم عبر طريقة POST فقط
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'طريقة الطلب غير مسموحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// استقبال البيانات المرسلة بصيغة JSON أو من نموذج مرسل (FormData)
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// إذا لم يتم إرسال بيانات JSON، جرب استقبالها عبر POST العادي
if (empty($data)) {
    $data = $_POST;
}

// التحقق من وجود نوع البيانات المراد حفظها (مثلاً: مقال، إعدادات، ودجت)
$type = isset($data['type']) ? trim($data['type']) : '';
$content = isset($data['content']) ? $data['content'] : null;

if (empty($type) || $content === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'بيانات غير كافية أو مفقودة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// تحديد مجلد الحفظ (تأكد من وجود مجلد باسم data ومنحه صلاحيات الكتابة 755 أو 777 إذا لزم الأمر)
$dataDir = __DIR__ . '/data/';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// تحديد اسم الملف بناءً على النوع لمنع الوصول لملفات غير مسموحة (Path Traversal Protection)
$allowedTypes = ['articles', 'settings', 'widgets'];
if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'نوع البيانات غير مدعوم.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$filename = $dataDir . $type . '.json';

// تنقية أو تجهيز البيانات قبل الحفظ (حسب الحاجة)
// ملاحظة: يُفضل إجراء التنقية الأساسية من جهة العميل (JavaScript) أيضاً

// محاولة حفظ البيانات في ملف JSON بشكل منظّم وجميل (JSON_PRETTY_PRINT)
$jsonData = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($jsonData === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل تحويل البيانات إلى صيغة JSON.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// استخدام قفل الملفات (LOCK_EX) لمنع التضارب عند حدوث طلبات حفظ متزامنة
if (file_put_contents($filename, $jsonData, LOCK_EX) !== false) {
    echo json_encode([
        'status' => 'success',
        'message' => 'تم حفظ البيانات بنجاح.',
        'type' => $type
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء الكتابة إلى الملف.'], JSON_UNESCAPED_UNICODE);
}
?>
