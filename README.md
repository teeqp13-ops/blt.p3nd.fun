# بوت توقيع تطبيقات IPA بلغة PHP مع GitHub Releases

هذا البوت يقوم بتوقيع تطبيقات iOS بصيغة IPA باستخدام أداة `zsign` وتوليد روابط تثبيت مباشرة (OTA)، بالإضافة إلى رفع التطبيقات الموقعة وملفات الـ plist إلى GitHub Releases.

## المتطلبات
1. سيرفر يعمل بنظام Linux (يفضل Ubuntu).
2. مثبت عليه `PHP` و `curl`.
3. مثبت عليه أداة `zsign`.
4. شهادة مطور Apple بصيغة `.p12` وملف بروفايل `.mobileprovision`.
5. حساب GitHub و Personal Access Token (PAT) بصلاحية `repo`.

## طريقة التركيب
1. قم بنسخ محتويات هذا المستودع إلى سيرفرك.
2. تأكد من إعطاء صلاحيات الكتابة للمجلدات التالية:
   - `uploads/`
   - `signed/`
   - `certs/`
3. قم بوضع ملف الشهادة (`cert.p12`) وملف البروفايل (`prov.mobileprovision`) داخل مجلد `certs/`.
4. افتح ملف `config.php` وقم بتعديل التالي:
   - `BOT_TOKEN`: توكن البوت من @BotFather (تم تحديثه بالفعل).
   - `ADMIN_ID`: معرف تيليجرام الخاص بك (اختياري، لإرسال إشعارات).
   - `SERVER_URL`: رابط المجلد على سيرفرك (يجب أن يدعم HTTPS).
   - `ZSIGN_PATH`: المسار إلى أداة zsign على السيرفر.
   - `GITHUB_REPO_OWNER`: اسم المستخدم الخاص بك على GitHub (تم تحديثه بالفعل).
   - `GITHUB_REPO_NAME`: اسم المستودع على GitHub (تم تحديثه بالفعل).
   - `GITHUB_TOKEN`: Personal Access Token الخاص بك على GitHub بصلاحية `repo`.
5. قم بتعيين Webhook للبوت عبر الرابط التالي:
   `https://api.telegram.org/botYOUR_TOKEN/setWebhook?url=https://your-domain.com/ipa-signer-bot/bot.php`

## ملاحظات هامة
- أداة `zsign` هي أداة سريعة لتوقيع التطبيقات ولا تحتاج إلى نظام macOS.
- يجب أن يكون الرابط `SERVER_URL` يدعم `HTTPS` بشهادة صالحة لكي يقبلها نظام iOS عند التثبيت.
- ملفات الـ `plist` يتم توليدها تلقائياً ورفعها إلى GitHub Releases لتسهيل التثبيت المباشر.
- تأكد من أن `GITHUB_TOKEN` لديه الصلاحيات اللازمة لإنشاء Releases ورفع الملفات إليها.
