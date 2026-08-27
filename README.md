# 📦 نظام إدارة المستودعات الشامل — Warehouse Management System

<div align="center">

![React](https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Vite](https://img.shields.io/badge/Vite-6.4-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-3-003B57?style=for-the-badge&logo=sqlite&logoColor=white)
![Sanctum](https://img.shields.io/badge/Laravel_Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Arabic RTL](https://img.shields.io/badge/Arabic-RTL-green?style=for-the-badge)

**نظام متكامل لإدارة المستودعات والمخزون** — واجهة أمامية بـ React (عربية RTL) + API خلفي بـ Laravel + قاعدة بيانات SQLite، مع تشغيل موحّد بأمر واحد.

</div>

---

## 📋 الفهرس

- [نظرة عامة](#-نظرة-عامة)
- [بنية المشروع](#-بنية-المشروع)
- [التقنيات المستخدمة](#-التقنيات-المستخدمة)
- [متطلبات التشغيل](#-متطلبات-التشغيل)
- [🚀 التشغيل بأمر واحد (الموصى به)](#-التشغيل-بأمر-واحد-الموصى-به)
- [التشغيل اليدوي (بديل)](#-التشغيل-اليدوي-بديل)
- [ميزات الواجهة الأمامية](#-ميزات-الواجهة-الأمامية)
- [ميزات الـ API الخلفية](#-ميزات-الapi-الخلفية)
- [أدوار المستخدمين](#-أدوار-المستخدمين)
- [نظرة عامة على الـ API](#-نظرة-عامة-على-الapi)
- [بنية قاعدة البيانات](#-بنية-قاعدة-البيانات)
- [أوامر مفيدة](#-أوامر-مفيدة)
- [استكشاف الأخطاء وإصلاحها](#-استكشاف-الأخطاء-وإصلاحها)
- [ملاحظات Git والتحكم بالإصدار](#-ملاحظات-git-والتحكم-بالإصدار)
- [الفريق](#-الفريق)
- [الترخيص](#-الترخيص)

---

## 🌟 نظرة عامة

النظام مبني على **حالة استخدام واقعية لإدارة المستودعات**، ويغطي دورة حياة المخزون الكاملة:

- طلبات الشراء → سندات الاستلام والتوريد → التخزين في المواقع → طلبات المواد → سجلات العهدة → سندات الهدر.

يتكوّن من جزأين:

| الجزء | المجلد | الدور |
|------|--------|-------|
| 🎨 الواجهة الأمامية (Frontend) | `Warehouse_React/` | واجهة عربية RTL بـ React + Vite + MUI + Tailwind (65+ مسارًا) |
| ⚙️ الخلفية (Backend) | `Warehouses-Laravel/` | REST API بـ Laravel + Sanctum |

الواجهة تتصل بالخلفية عبر `http://127.0.0.1:8000/api`، وتوجد أيضًا بروكسي في إعدادات Vite تحوّل أي طلب يبدأ بـ `/api` إلى الخادم الخلفي.

---

## 🏗️ بنية المشروع

```
D:\WHS\
│
├── start.ps1                    ← التشغيل الموحد (أمر واحد)
├── start.bat                    ← غلاف للتشغيل بنقرة واحدة
│
├── Warehouse_React\             ← الواجهة الأمامية (React + Vite)
│   ├── src\
│   │   ├── api\axiosInstance.js     # إعدادات Axios وعنوان الخادم
│   │   ├── components\              # ~37 مكوّن مشترك (Layout, Dashboard, UI)
│   │   ├── pages\                   # ~76 صفحة (Auth، Dashboard، Products، Notes...)
│   │   ├── print\                   # أداة طباعة مستندات A4
│   │   ├── utils\ and data\         # أدوات وبيانات ثابتة
│   │   └── App.jsx                  # الموجّه الرئيسي (65 مسارًا)
│   ├── index.html
│   ├── vite.config.js
│   └── package.json
│
└── Warehouses-Laravel\          ← الخلفية (Laravel API)
    ├── app\                     # Controllers, Models, Middleware
    ├── routes\
    │   ├── api.php              # كل نقاط الـ API
    │   └── web.php
    ├── database\
    │   ├── migrations\          # 30+ جدول
    │   ├── seeders\             # بيانات أولية (مستودعات، أقسام...)
    │   └── database.sqlite      # قاعدة البيانات المحلية (تُنشأ تلقائيًا)
    ├── .env                     # إعدادات البيئة (يُنشأ تلقائيًا من .env.example)
    ├── artisan
    └── composer.json
```

معمارية النظام:

```
┌──────────────────────────────────────────────┐
│   المتصفح → React + Vite  (http://localhost:5173)  │
└──────────────────────┬───────────────────────┘
                       │ HTTP /api  (تحتاج توكن Sanctum)
┌──────────────────────▼───────────────────────┐
│   Laravel REST API  (http://127.0.0.1:8000/api)   │
│   Auth (Sanctum) • Controllers • Middleware  │
└──────────────────────┬───────────────────────┘
                       │ Eloquent ORM
┌──────────────────────▼───────────────────────┐
│             SQLite Database                  │
└──────────────────────────────────────────────┘
```

---

## 🛠️ التقنيات المستخدمة

| الطبقة | التقنية |
|--------|---------|
| الواجهة الأمامية | React 19 • Vite 6.4 • React Router 7 • Tailwind CSS 4 • MUI 7 • Axios • Chart.js • FullCalendar 6 • Notistack |
| الخلفية | Laravel 12 • PHP 8.2 • Laravel Sanctum (Auth بتوكن) |
| قاعدة البيانات | SQLite (الافتراضي محليًا — ويدعم MySQL أيضًا) |
| اللغة/الواجهة | عربية RTL + وضع فاتح/داكن |

---

## ⚙️ متطلبات التشغيل

| الأداة | الحد الأدنى |
|--------|-------------|
| Node.js | 18+ (يُفضَّل 20+) |
| PHP | 8.2+ |
| Composer | 2.x |
| PowerShell | 5.1 أو 7 (بنظام Windows) |
| إضافة PHP | `pdo_sqlite` (مفعلة افتراضيًا في XAMPP) |

> ملاحظة: لا حاجة لتثبيت أي شيء يدويًا — سكربت التشغيل الموحد يثبّت كل المتطلبات تلقائيًا في أول تشغيل.

---

## 🚀 التشغيل بأمر واحد (الموصى به)

من داخل المجلد الجذر **`D:\WHS`**، نفّذ أحد الأمرين:

```powershell
# الخيار 1 — عبر PowerShell
.\start.ps1

# الخيار 2 — عبر نافذة الأوامر (CMD) أو نقرة مزدوجة
start.bat
```

### ماذا يفعل السكربت تلقائيًا؟

| الخطوة | عند أول تشغيل | عند ما بعده |
|--------|:---:|:---:|
| تثبيت تبعيات Laravel (`composer install`) | ✅ | يتخطّاها |
| إنشاء `.env` من `.env.example` + توليد `APP_KEY` | ✅ | يتخطّاها |
| إنشاء قاعدة `database/database.sqlite` | ✅ | يتخطّاها |
| تشغيل `php artisan migrate` | ✅ | ✅ (يتخطّى المنفَّذ) |
| تثبيت تبعيات React (`npm install`) | ✅ | يتخطّاها |
| تشغيل الخادمين معًا في نفس النافذة | ✅ | ✅ |

عندما ينجح كل شيء ستظهر رسالة:

```
==================================================
  Both servers are UP
  Frontend : http://localhost:5173
  Backend  : http://127.0.0.1:8000/api
  Press Ctrl+C to stop.
==================================================
```

### الروابط بعد التشغيل

| الخدمة | الرابط |
|--------|--------|
| 🌐 الواجهة الأمامية (Frontend) | **http://localhost:5173** |
| 🔌 الخادم الخلفي / الـ API | **http://127.0.0.1:8000/api** |

> **مهم:** الواجهة تعمل عبر `localhost` وليس `127.0.0.1` — هذا لأن Vite في بيئتك الحالية يرتبط بعنوان الـ IPv6 (`::1`). افتح دائمًا **http://localhost:5173**.

### إيقاف التشغيل

اضغط `Ctrl+C` في نافذة السكربت. السكربت يوقف السيرفرين ويُفرّغ المنفذين `8000` و `5173` تلقائيًا.

### سلوك عند الفشل

السكربت **لا ينتظر إلى أجل غير مسمّى**: يمنح 120 ثانية كحد أقصى لإقلاع السيرفرين، وإذا فشل أحدهما يعرض حالة كل عملية والخطأ من السجل ثم يخرج بكود خطأ.

---

## 🔧 التشغيل اليدوي (بديل)

لو أردت تشغيل كل مشروع في Terminal مستقل (مثلًا أثناء التطوير):

### 1) الخلفية (Backend)

```powershell
cd D:\WHS\Warehouses-Laravel
composer install                 # مرة واحدة
copy .env.example .env           # مرة واحدة (إن لم يوجد)
php artisan key:generate         # مرة واحدة
php artisan migrate              # إنشاء الجداول
php artisan serve                # يعمل على http://127.0.0.1:8000
```

### 2) الواجهة الأمامية (Frontend)

```powershell
cd D:\WHS\Warehouse_React
npm install                      # مرة واحدة
npm run dev                      # يعمل على http://localhost:5173
```

---

## ✨ ميزات الواجهة الأمامية

### 🗂️ سير عمل الوثائق
- **سندات إدخال** — تسجيل البضائع الواردة بنماذج متعددة الأصناف
- **سندات إخراج** — تسجيل البضائع الصادرة
- **سندات استلام** — تأكيد توريد المورّد وربطها بطلبات الشراء
- **سندات هدر** — توثيق المواد التالفة بموافقة المدير
- **طلبات شراء** — سير عمل شراء مع اختيار المورّد
- **تقارير تركيب** — تتبع استهلاك المواد بمسار موافقة
- **إدارة العهدة** — إسناد أصناف للمستخدمين وتتبع الإرجاع

### 📦 إدارة المخزون
- عرض وتصفية المنتجات وتفاصيلها
- أرصدة المنتجات حسب المستودع
- سجل حركة المنتجات مع تتبع شهري
- تخصيص مواقع التخزين والبحث عن المواقع المتاحة
- تتبع الأصناف غير المعيّنة لموقع تخزين

### 👥 تحكم متعدد الأدوار
- حماية المسارات حسب الدور: `warehouseKeeper`، `warehouse`، `manager`
- سير عمل موافقة/رفض للمدير على طلبات المواد والتقارير
- إمكانية تعديل الكميات من المدير

### 📊 لوحة التحكم والتحليلات
- بطاقات ملخّصة + رسوم بيانية بـ Chart.js
- تقويم تفاعلي (FullCalendar) مع ملاحظات
- مركز إشعارات مع «تحديد الكل كمقروء»

### 🖨️ طباعة المستندات
- أداة طباعة A4 بصيغة حكومية لمستندات الإدخال/الإخراج/الاستلام/العهدة

### 🌙 الواجهة والمظهر
- تخطيط عربي RTL كامل • ثيمات فاتح/داكن بـ MUI • تصميم متجاوب بـ Tailwind

---

## ⚙️ ميزات الـ API الخلفية

- **المخزون والأرصدة:** تتبع المنتجات عبر مستودعات ومواقع متعددة، أرصدة حية، سجل حركة وتدقيق.
- **إدارة الوثائق:** سندات إدخال/إخراج/استلام.
- **سير عمل الطلبات:** طلبات مواد + طلبات شراء بمسار موافقة (معلّق ← موافق/مرفوض).
- **العهدة والأصول:** إسناد أصناف لمستخدمين وغرف محددة، إرجاع العهدة بتتبع كامل، إسناد جماعي.
- **التركيب والهدر:** تقارير تركيب مع استهلاك المواد + سندات هدر بموافقة المدير.
- **الإشعارات والتقويم:** نظام إشعارات متعدد الأشكال (polymorphic) + ملاحظات تقويم شخصية لكل مستخدم.
- **نقاط نهاية:** 30+ نقطة RESTful.

---

## 👥 أدوار المستخدمين

| الدور | الصلاحيات |
|------|-----------|
| 🏭 **WarehouseKeeper** | إدارة المخزون، إنشاء سندات إدخال/إخراج/استلام، التعامل مع طلبات الشراء |
| 👔 **Manager** | موافقة/رفض طلبات المواد وتقارير التركيب وسندات الهدر، تعديل الكميات |
| 🧑‍💼 **User** | تقديم طلبات المواد، عرض أصناف العهدة، لوحة شخصية |

---

## 🔌 نظرة عامة على الـ API

جميع النقاط تُسبق بـ `/api` (مثال: `http://127.0.0.1:8000/api/login`). معظمها محمي بمصادقة `auth:sanctum` (توكن يُرسل بالترويسة `Authorization: Bearer <token>`).

### 🔐 المصادقة (عامة)
| الطريقة | المسار | الوصف |
|--------|--------|-------|
| POST | `/login` | تسجيل الدخول والحصول على التوكن |
| POST | `/register` | إنشاء حساب جديد |
| POST | `/logout` | تسجيل الخروج (محمي) |

### 📦 المنتجات
| الطريقة | المسار |
|--------|--------|
| GET | `/products` • `/products/search` • `/products/{id}/details` |
| POST | `/products/store` • `/products/update/{id}` |
| DELETE | `/products/delete/{id}` |

### 🏭 المستودعات والمواقع
| الطريقة | المسار |
|--------|--------|
| GET/POST | `/warehouses` • `/locations` • `/buildings` • `/rooms` • `/departments` |
| POST | `/assign-location` |
| GET | `/unassigned-items` |

### 📋 طلبات المواد والشراء
| الطريقة | المسار |
|--------|--------|
| POST | `/MRequest` |
| GET | `/pendingRequestMaterial` • `/allRequestMaterial` |
| PUT | `/materialRequests/{id}/approve` • `/materialRequests/{id}/reject` |
| GET/POST | `/purchase-requests` |

### 📑 السندات
`/entryNote` • `/exitNote` • `/receivingNote` (إنشاء/استعراض/تفاصيل).

### 🗂️ العهدة
`/custody/store` • `/custody/showAll` • `/custody-returns` (إرجاع + قوائم معلّقة).

### 📊 التقارير
`/product-movements/{id}` • `/products/monthlyBalances` • `/warehouses/{id}/product-balances` • `/installation-reports/dashboard-stats`.

---

## 🗄️ بنية قاعدة البيانات

30+ جدولًا عبر `database/migrations`، وتشمل:

```
المستخدمون والأدوار   → users, managers, warehouse_keepers
المواقع               → warehouses, buildings, rooms, locations, departments
المنتجات والمخزون     → products, stocks, product_locations, product_movements
المورّدون والشراء     → suppliers, purchase_requests, purchase_request_items
تدفق المواد          → material_requests, entry_notes, exit_notes, receiving_notes
العهدة والأصول       → custodies, custody_items, custody_returns
العمليات             → installation_reports, scrap_notes, calendar_notes
النظام               → notifications, personal_access_tokens, sessions, jobs, cache
```

> تُنشأ القاعدة وتُنفَّذ الجداول تلقائيًا عند أول تشغيل عبر السكربت الموحد. توجد أيضًا بيانات أولية (seeders) للمستودعات والأقسام والمواقع والمنتجات.

---

## 🔨 أوامر مفيدة

### الخلفية
```powershell
php artisan migrate            # تشغيل باقي الجداول
php artisan db:seed            # تعبئة البيانات الأولية
php artisan make:model X       # إنشاء موديل جديد
```

### الواجهة
```powershell
npm run dev        # خادم التطوير
npm run build      # بناء نسخة إنتاج → dist/
npm run preview    # معاينة النسخة المبنية
npm run lint       # فحص ESLint
```

---

## 🔍 استكشاف الأخطاء وإصلاحها

| المشكلة | الحل |
|--------|------|
| `127.0.0.1:5173` لا يعمل (رفض الاتصال) | **وضع طبيعي** — افتح `http://localhost:5173` (Vite هنا على IPv6) |
| «المنفذ مشغول» عند التشغيل | السكربت يفرّغ المنفذين تلقائيًا؛ وإلا أنهِ عمليّات `php`/`node` القديمة من مدير المهام |
| أول تشغيل بطيء (عدة دقائق) | طبيعي — `composer install` و `npm install` يُشغّلان مرة واحدة فقط |
| خطأ `pdo_sqlite` غير موجود | فعّل إضافة `pdo_sqlite` من `php.ini` وأعد تشغيل PowerShell |
| فشل `php artisan migrate` | راجع رسالة الخطأ (غالبًا مشكلة في `.env`) واحذف جدول migrations إذا لزم التكرار |
| الواجهة تعمل لكن لا توجد بيانات | نفّذ `php artisan migrate --seed` داخل مجلد Laravel (خياري) |

---

## 🗂️ ملاحظات Git والتحكم بالإصدار

- `Warehouse_React/` و `Warehouses-Laravel/` **مستودعان مستقلان**، والجذر `D:\WHS` **ليس مستودعًا**.
- ملفات التشغيل `start.ps1` و `start.bat` في الجذر **خارج** المستودعين → لن تُضمّن في أي `git push`. إن أردت مشاركتها مع الفريق انسخها داخل أحد المستودعين أو أنشئ مستودعًا للجذر.
- جميع ملفات التشغيل المؤقتة **مستثناة من Git** في المستودعين: `.env`، `database/database.sqlite`، `vendor/`، `node_modules/` — فالرفع لن يُحمّل أي ملف إعدادات أو بيانات محلية (تحقّق: `git status` نظيف).

---

## 👨‍💻 الفريق

**الواجهة الأمامية:**
- [Ammar Dahhan](https://github.com/AmmarDahhan) — React/UI
- [Malak Mobark](https://github.com/MalakMobark) — React/UI
- [Hadi Al-Gafer](https://github.com/MG22Hadi) — معماري الواجهة + RTL + الخلفية

**الخلفية:**
- [Hadi Al-Gafer](https://github.com/MG22Hadi) — تصميم وبناء الـ API
- [Nour Haies](https://github.com/nourahaies) — تصميم وبناء الـ API

---

## 📄 الترخيص

مشروع أكاديمي/معرض أعمال (portfolio) مبني كدراسة حالة واقعية لإدارة المستودعات.