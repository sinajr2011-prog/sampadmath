<?php
// اتصال به دیتابیس
$host = 'localhost';
$dbname = 'ertxxmye_kharazmica';
$username = 'ertxxmye_kharazmica_user';
$password = '@Sina09945417131';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    session_start();
    
   
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        if ($_POST['action'] === 'check_auth') {
            $response = ['logged_in' => isset($_SESSION['user_id'])];
            echo json_encode($response);
            exit;
        }
    }
    
    
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    $action = $_POST['action'] ?? '';
    
    // ثبت‌نام
    if ($action === 'register') {
        $fullname = $_POST['fullname'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($fullname) || empty($phone) || empty($password)) {
            $response['message'] = 'لطفاً تمام فیلدها را پر کنید.';
        } elseif (!preg_match('/^09[0-9]{9}$/', $phone)) {
            $response['message'] = 'شماره موبایل باید ۱۱ رقم و با 09 شروع شود.';
        } elseif ($password !== $confirm) {
            $response['message'] = 'رمز عبور و تکرار آن مطابقت ندارند.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $response['message'] = 'این شماره موبایل قبلاً ثبت شده است.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (fullname, phone, password_hash) VALUES (?, ?, ?)");
                if ($stmt->execute([$fullname, $phone, $hashed])) {
                    $response['success'] = true;
                    $response['message'] = 'ثبت‌نام موفق! اکنون وارد شوید.';
                } else {
                    $response['message'] = 'خطا در ثبت‌نام.';
                }
            }
        }
    }
    
    // ورود
    elseif ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE fullname = ? OR phone = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['phone'] = $user['phone'];
            $response['success'] = true;
            $response['message'] = 'ورود موفق! در حال انتقال...';
            $response['redirect'] = 'kharazmika.html';
        } else {
            $response['message'] = 'نام کاربری/شماره یا رمز اشتباه است.';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ورود | ثبت‌نام | خوارزمیکا</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
  <style>
    /* ==================== استایل‌ها ==================== */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at top left, #0f172a, #020617); padding: 20px; }
    .auth-container { width: 100%; max-width: 450px; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(12px); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 32px; padding: 40px 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8); }
    
    .logo-area { display: flex; flex-direction: column; align-items: center; margin-bottom: 35px; }
    .logo-icon { width: 70px; height: 70px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 36px; color: white; margin-bottom: 15px; box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.5); }
    h2 { color: #f1f5f9; font-weight: 800; font-size: 28px; }
    .auth-sub { color: #94a3b8; font-size: 14px; margin-top: 5px; }

    .tabs { display: flex; gap: 10px; margin-bottom: 35px; background: rgba(0, 0, 0, 0.2); padding: 6px; border-radius: 60px; }
    .tab-btn { flex: 1; padding: 12px; border: none; background: transparent; color: #94a3b8; font-size: 16px; font-weight: 600; border-radius: 60px; cursor: pointer; transition: all 0.3s; }
    .tab-btn.active { background: #6366f1; color: white; box-shadow: 0 8px 16px -6px rgba(99, 102, 241, 0.5); }

    .form-panel { display: none; }
    .form-panel.active { display: block; }

    .input-group { margin-bottom: 24px; }
    .input-label { display: block; margin-bottom: 8px; color: #cbd5e1; font-weight: 500; font-size: 14px; }
    .input-wrapper { position: relative; }
    .input-icon { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 18px; }
    .auth-input { width: 100%; padding: 16px 48px 16px 16px; background: #0f172a; border: 1.5px solid #334155; border-radius: 16px; font-size: 16px; color: white; transition: all 0.2s; direction: ltr; text-align: left; }
    .auth-input:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2); }
    .auth-input:focus + .input-icon { color: #a78bfa; }

    .password-toggle { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; cursor: pointer; }

    .form-options { display: flex; align-items: center; margin: 25px 0 30px; font-size: 14px; }
    .remember-check { display: flex; align-items: center; gap: 8px; color: #cbd5e1; cursor: pointer; }
    .checkbox-custom { width: 20px; height: 20px; border: 2px solid #475569; border-radius: 6px; display: flex; align-items: center; justify-content: center; margin-left: 5px; color: white; background: transparent; }
    .checkbox-custom.checked { background: #6366f1; border-color: #6366f1; }
    .checkbox-custom i { font-size: 12px; opacity: 0; }
    .checkbox-custom.checked i { opacity: 1; }

    .auth-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border: none; border-radius: 16px; color: white; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 20px -8px rgba(99, 102, 241, 0.5); display: flex; align-items: center; justify-content: center; gap: 8px; }
    .auth-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -10px rgba(139, 92, 246, 0.6); }

    .strength-meter { margin-top: 8px; height: 4px; background: #334155; border-radius: 4px; }
    .strength-fill { height: 100%; width: 0%; border-radius: 4px; transition: all 0.3s; }
    .strength-text { font-size: 12px; margin-top: 4px; color: #94a3b8; }

    .message-toast { margin-top: 20px; padding: 14px 18px; border-radius: 14px; font-size: 14px; text-align: center; display: none; }
    .message-toast.show { display: block; animation: fadeSlide 0.3s; }
    .message-toast.error { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; }
    .message-toast.success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7; }

    @keyframes fadeSlide { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .fa-spinner { animation: spin 1s linear infinite; }
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
  </style>
</head>
<body>
<div class="auth-container">
  <div class="logo-area">
    <div class="logo-icon">∑</div>
    <h2>خوارزمیکا</h2>
    <div class="auth-sub">سکوی جامع ریاضی</div>
  </div>

  <div class="tabs">
    <button class="tab-btn active" data-tab="login">ورود</button>
    <button class="tab-btn" data-tab="register">ثبت‌نام</button>
  </div>

  <!-- پنل ورود -->
  <div class="form-panel active" id="loginPanel">
    <form id="loginForm">
      <div class="input-group">
        <label class="input-label">نام کاربری</label>
        <div class="input-wrapper">
          <input type="text" class="auth-input" id="loginUsername" placeholder="username" dir="ltr" required>
          <i class="fas fa-user input-icon"></i>
        </div>
      </div>
      <div class="input-group">
        <label class="input-label">رمز عبور</label>
        <div class="input-wrapper">
          <input type="password" class="auth-input" id="loginPassword" placeholder="••••••••" dir="ltr" required>
          <i class="fas fa-lock input-icon"></i>
          <i class="fas fa-eye password-toggle" data-target="loginPassword"></i>
        </div>
      </div>
      <div class="form-options">
        <div class="remember-check" id="rememberCheck">
          <div class="checkbox-custom" id="rememberCheckbox"><i class="fas fa-check"></i></div>
          <span>مرا به خاطر بسپار</span>
        </div>
      </div>
      <button type="submit" class="auth-submit" id="loginBtn"><i class="fas fa-sign-in-alt"></i> ورود</button>
    </form>
  </div>

  <!-- پنل ثبت‌نام -->
  <div class="form-panel" id="registerPanel">
    <form id="registerForm">
      <div class="input-group">
        <label class="input-label">نام کاربری (حداقل ۳ کاراکتر)</label>
        <div class="input-wrapper">
          <input type="text" class="auth-input" id="regUsername" placeholder="username" dir="ltr" required minlength="3">
          <i class="fas fa-user input-icon"></i>
        </div>
      </div>
       <div class="input-group">
        <label class="input-label">شماره تلفن</label>
        <div class="input-wrapper">
          <input type="text" class="auth-input" id="regPhone" placeholder="با فرمت 09123456789 وارد کنید" dir="ltr" required minlength="3">
          <i class="fas fa-phone input-icon"></i>
        </div>
      </div>
      <div class="input-group">
        <label class="input-label">رمز عبور (حداقل ۶ کاراکتر)</label>
        <div class="input-wrapper">
          <input type="password" class="auth-input" id="regPassword" placeholder="••••••••" dir="ltr" required minlength="6">
          <i class="fas fa-lock input-icon"></i>
          <i class="fas fa-eye password-toggle" data-target="regPassword"></i>
        </div>
        <div class="strength-meter"><div class="strength-fill" id="strengthFill"></div></div>
        <div class="strength-text" id="strengthText">قدرت رمز</div>
      </div>
      <div class="input-group">
        <label class="input-label">تکرار رمز عبور</label>
        <div class="input-wrapper">
          <input type="password" class="auth-input" id="regConfirmPassword" placeholder="••••••••" dir="ltr" required>
          <i class="fas fa-check-circle input-icon"></i>
          <i class="fas fa-eye password-toggle" data-target="regConfirmPassword"></i>
        </div>
      </div>
      <button type="submit" class="auth-submit" id="registerBtn"><i class="fas fa-user-plus"></i> ثبت‌نام</button>
    </form>
  </div>

  <div id="messageToast" class="message-toast"></div>
  <div style="margin-top: 20px; text-align: center; font-size: 12px; color: #475569;">✨ S.J & K.SH ✨</div>
</div>

<script>
  (function(){
    // ==================== المان‌ها ====================
    const tabs = document.querySelectorAll('.tab-btn');
    const loginPanel = document.getElementById('loginPanel');
    const registerPanel = document.getElementById('registerPanel');
    const messageToast = document.getElementById('messageToast');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginUsername = document.getElementById('loginUsername');
    const loginPassword = document.getElementById('loginPassword');
    const regUsername = document.getElementById('regUsername');
    const regPhone = document.getElementById('regPhone');
    const regPassword = document.getElementById('regPassword');
    const regConfirm = document.getElementById('regConfirmPassword');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const rememberCheckboxDiv = document.getElementById('rememberCheckbox');
    
    let isRememberChecked = false;

    // ==================== توابع کمکی ====================
    function showMessage(text, type = 'error') {
      messageToast.textContent = text;
      messageToast.className = `message-toast show ${type}`;
      setTimeout(() => messageToast.classList.remove('show'), 4000);
    }

    function checkPasswordStrength(password) {
      let strength = 0;
      if (password.length >= 6) strength++;
      if (password.length >= 10) strength++;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
      if (/[0-9]/.test(password)) strength++;
      if (/[^a-zA-Z0-9]/.test(password)) strength++;
      return Math.min(strength, 4);
    }

    function updatePasswordStrength(password) {
      const level = checkPasswordStrength(password);
      const percent = (level / 4) * 100;
      strengthFill.style.width = percent + '%';
      
      if (level === 0) { strengthFill.style.background = '#ef4444'; strengthText.textContent = 'خیلی ضعیف'; }
      else if (level === 1) { strengthFill.style.background = '#f97316'; strengthText.textContent = 'ضعیف'; }
      else if (level === 2) { strengthFill.style.background = '#eab308'; strengthText.textContent = 'متوسط'; }
      else if (level === 3) { strengthFill.style.background = '#3b82f6'; strengthText.textContent = 'خوب'; }
      else { strengthFill.style.background = '#10b981'; strengthText.textContent = 'عالی'; }
    }

    function switchTab(tabId) {
      tabs.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabId));
      loginPanel.classList.toggle('active', tabId === 'login');
      registerPanel.classList.toggle('active', tabId === 'register');
      messageToast.classList.remove('show');
    }

    // ==================== Event Listeners ====================
    tabs.forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.tab)));

    document.querySelectorAll('.password-toggle').forEach(toggle => {
      toggle.addEventListener('click', function() {
        const input = document.getElementById(this.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
      });
    });

    rememberCheckboxDiv.addEventListener('click', function() {
      isRememberChecked = !isRememberChecked;
      this.classList.toggle('checked', isRememberChecked);
    });

    regPassword.addEventListener('input', () => updatePasswordStrength(regPassword.value));

    // ==================== ثبت‌نام با سرور ====================
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fullname = regUsername.value.trim();
      const phone = regPhone.value.trim();
      const password = regPassword.value;
      const confirm = regConfirm.value;

      if (fullname.length < 3) return showMessage('نام کاربری حداقل ۳ کاراکتر', 'error');
      if (password.length < 6) return showMessage('رمز عبور حداقل ۶ کاراکتر', 'error');
      if (password !== confirm) return showMessage('رمز عبور و تکرار آن مطابقت ندارند', 'error');
      
      const formData = new FormData();
      formData.append('action', 'register');
      formData.append('fullname', fullname);
      formData.append('phone', phone);
      formData.append('password', password);
      formData.append('confirm_password', confirm);
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await response.json();
        showMessage(data.message, data.success ? 'success' : 'error');
        if (data.success) {
          registerForm.reset();
          updatePasswordStrength('');
          switchTab('login');
          loginUsername.value = fullname;
        }
      } catch(err) {
        showMessage('خطا در ارتباط با سرور', 'error');
      }
    });

    // ==================== ورود با سرور ====================
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const username = loginUsername.value.trim();
      const password = loginPassword.value;

      if (!username || !password) return showMessage('نام کاربری و رمز را وارد کنید', 'error');
      
      const formData = new FormData();
      formData.append('action', 'login');
      formData.append('username', username);
      formData.append('password', password);
      
      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData
        });
        const data = await response.json();
        showMessage(data.message, data.success ? 'success' : 'error');
        if (data.success && data.redirect) {
          setTimeout(() => window.location.href = 'kharazmika.html', 1000);
        }
      } catch(err) {
        showMessage('خطا در ارتباط با سرور', 'error');
      }
    });
  })();
</script>
</body>
</html>
