<?php
// إعداد رأس الصفحة ليكون بصيغة JSON وتحديد ترميز الأحرف
header('Content-Type: application/json; charset=UTF-8');

// التأكد من أن الطلب يتم عبر طريقة POST فقط
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'طريقة الطلب غير مسموحة.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// استقبال البيانات المرسلة
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (empty($data)) {
    $data = $_POST;
}

// تحديد القسم المراد حفظه (settings, articles, widgets, menus, ads)
$type = isset($data['type']) ? trim($data['type']) : '';
$content = isset($data['content']) ? $data['content'] : null;

// الأنواع المسموحة لتجنب أي تلاعب بالمسارات (Path Traversal)
$allowedTypes = ['settings', 'articles', 'widgets', 'menus', 'ads'];

if (empty($type) || !in_array($type, $allowedTypes) || $content === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'بيانات غير كافية أو نوع غير مدعوم.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// إعداد مجلد الحفظ
$dataDir = __DIR__ . '/data/';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$filename = $dataDir . $type . '.json';

// معالجة خاصة للمقالات لضمان نظافة وثبات الـ slug وتجنب أي مشاكل في الروابط
if ($type === 'articles' && is_array($content)) {
    foreach ($content as &$article) {
        if (isset($article['title']) && (empty($article['slug']) || trim($article['slug']) === '')) {
            // توليد slug آمن تلقائياً في حال لم يتم إرساله (أحرف إنجليزية، أرقام، وشرطات)
            $slug = strtolower(trim($article['title']));
            $slug = preg_replace('/[^\w\s-]/u', '', $slug);
            $slug = preg_replace('/[\s_-]+/', '-', $slug);
            $slug = trim($slug, '-');
            // إذا كان العنوان عربياً بالكامل ولم ينتج slug إنجليزي، نستخدم معرفاً زمنياً أو نحتفظ بالنص مع ترميزه
            if (empty($slug)) {
                $slug = 'post-' . time() . '-' . rand(100, 999);
            }
            $article['slug'] = $slug;
        } elseif (isset($article['slug'])) {
            // تنظيف الـ slug المدخل يدوياً ليكون آمناً للرابط
            $article['slug'] = trim(preg_replace('/[^\w\-]/u', '', $article['slug']));
        }
    }
    unset($article);
}

// تحويل البيانات إلى JSON مع الحفاظ على التنسيق واللغة العربية
$jsonData = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($jsonData === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل معالجة البيانات.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// الحفظ مع قفل الملف لمنع التضارب (LOCK_EX)
if (file_put_contents($filename, $jsonData, LOCK_EX) !== false) {
    echo json_encode([
        'status' => 'success',
        'message' => 'تم حفظ بيانات (' . $type . ') بنجاح.',
        'type' => $type
    ], JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء الكتابة إلى الملف.'], JSON_UNESCAPED_UNICODE);
}
?>
