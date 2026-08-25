<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = db();

$email = $_GET['email'] ?? '';
$error = '';
$csrf_token = generateCSRFToken();

$initialResendCooldown = 0;
if ($email !== '') {
    $stmt = $pdo->prepare("SELECT created_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $lastRequest = $stmt->fetchColumn();

    if ($lastRequest) {
        $initialResendCooldown = max(
            0,
            OTP_REQUEST_COOLDOWN - (time() - strtotime($lastRequest))
        );
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRFToken($_POST['csrf_token'] ?? '');

    $email = $_POST['email'] ?? $_GET['email'] ?? '';
    $otp   = trim($_POST['otp'] ?? '');

    if (empty($email) || empty($otp)) {
        $error = "OTP is required";
    } else {

        $stmt = $pdo->prepare("
            SELECT * FROM password_resets
            WHERE email = ?
            AND expires_at > ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$email, date('Y-m-d H:i:s')]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $error = "Invalid or expired OTP";
        } elseif ($reset['attempts'] >= MAX_OTP_ATTEMPTS) {
            $error = "Too many incorrect attempts. Please request a new code.";
        } elseif (!hash_equals((string)$reset['otp'], $otp)) {

            $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?")
                ->execute([$reset['id']]);

            $error = "Invalid or expired OTP";

        } else {

            header("Location: reset_password.php?token=" . $reset['token']);
            exit();
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP</title>

<link rel="stylesheet" href="../assets/css/index.css">

<style>
body {
    background: #f5f7fb;
    font-family: 'Inter', sans-serif;
}

/* Container */
.otp-wrapper {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Card */
.otp-card {
    background: #fff;
    padding: 35px;
    border-radius: 16px;
    width: 360px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    text-align: center;
}

/* Title */
.otp-card h2 {
    margin-bottom: 8px;
    color: #5097A4;
}

.otp-card p {
    font-size: 14px;
    color: #777;
    margin-bottom: 25px;
}

/* OTP Boxes */
.otp-inputs {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 25px;
}

.otp-inputs input {
    width: 45px;
    height: 50px;
    font-size: 20px;
    text-align: center;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
    transition: 0.2s;
}

.otp-inputs input:focus {
    border-color: #5097A4;
    box-shadow: 0 0 8px rgba(80,151,164,0.3);
}

/* Button */
.btn {
    width: 100%;
    padding: 12px;
    background: #5097A4;
    color: #fff;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

.btn:hover {
    background: #3f7f8a;
}

/* Responsive */
@media(max-width: 420px) {
    .otp-card {
        width: 90%;
    }
}
</style>
</head>

<body>



<div class="otp-wrapper">
    <div class="otp-card">

        <h2>Verify OTP</h2>

        <?php if ($error): ?>
    <div style="background:#ffe5e5;color:#c00;padding:10px;border-radius:8px;margin-bottom:10px;">
        <?= $error ?>
    </div>
<?php endif; ?> 

        <p>Enter the 6-digit code sent to your email</p>
        <div id="timer" style="font-weight:700;color:#5097A4;margin-bottom:18px;">10:00</div>

        <form method="POST" action="verify_otp.php?email=<?= urlencode($email) ?>">

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

    <div class="otp-inputs">
        <input type="text" maxlength="1" class="otp">
        <input type="text" maxlength="1" class="otp">
        <input type="text" maxlength="1" class="otp">
        <input type="text" maxlength="1" class="otp">
        <input type="text" maxlength="1" class="otp">
        <input type="text" maxlength="1" class="otp">
    </div>

    <input type="hidden" name="otp" id="fullOtp">

    <button type="submit" class="btn">Verify OTP</button>
</form> 


        <div style="margin-top:15px; text-align:center;">
    <button type="button" id="resendBtn" disabled class="btn" style="opacity:0.6;">
        Resend OTP (60s)
    </button>
</div> 

    </div>
</div>

<script>
const inputs = document.querySelectorAll('.otp');

inputs.forEach((input, index) => {

    input.addEventListener('input', (e) => {
        if (e.target.value.length === 1 && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }

        updateOtp();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === "Backspace" && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});

// combine OTP
function updateOtp() {
    let otp = "";
    inputs.forEach(i => otp += i.value);
    document.getElementById('fullOtp').value = otp;
} 


// paste support
document.addEventListener("paste", function(e) {
    let paste = (e.clipboardData || window.clipboardData).getData('text');
    if (paste.length === 6 && !isNaN(paste)) {
        inputs.forEach((input, i) => {
            input.value = paste[i] || "";
        });
        updateOtp();
    }
});

let timeLeft = <?= (int)OTP_EXPIRY ?>;
let resendCooldown = <?= (int)OTP_REQUEST_COOLDOWN ?>;

const timerEl = document.getElementById("timer");
const resendBtn = document.getElementById("resendBtn");
let resendInterval = null;

/* =========================
   MAIN OTP EXPIRY TIMER
========================= */
function updateTimer() {
    let minutes = Math.floor(timeLeft / 60);
    let seconds = timeLeft % 60;
    seconds = seconds < 10 ? "0" + seconds : seconds;

    if (timerEl) {
        timerEl.textContent = `${minutes}:${seconds}`;
    }

    if (timeLeft <= 0) {
        clearInterval(mainTimer);
        if (timerEl) timerEl.textContent = "Expired";
        return;
    }

    timeLeft--;
}

const mainTimer = setInterval(updateTimer, 1000);
updateTimer();

/* =========================
   RESEND COOLDOWN TIMER
========================= */
function startResendCooldown(seconds = resendCooldown) {
    clearInterval(resendInterval);

    resendBtn.disabled = true;
    resendBtn.style.opacity = "0.6";

    let cooldown = Math.max(0, Number(seconds));

    const tick = () => {
        if (cooldown <= 0) {
            clearInterval(resendInterval);
            resendBtn.disabled = false;
            resendBtn.style.opacity = "1";
            resendBtn.textContent = "Resend OTP";
            return;
        }

        resendBtn.textContent = `Resend in ${cooldown}s`;
        cooldown--;
    };

    tick();
    resendInterval = setInterval(tick, 1000);
}

/* Start the cooldown immediately when the page loads.
   The old version only started it after a successful click,
   leaving the button permanently disabled for new OTP requests. */
startResendCooldown(<?= (int)$initialResendCooldown ?>);

/* =========================
   RESEND CLICK EVENT
========================= */
resendBtn.addEventListener("click", async function () {
    resendBtn.disabled = true;

    try {
        const response = await fetch("resend_otp.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "same-origin"
        });

        const data = await response.json();

        if (data.status === "success") {
            alert("OTP resent successfully.");
            timeLeft = <?= (int)OTP_EXPIRY ?>;
            updateTimer();
            startResendCooldown(<?= (int)OTP_REQUEST_COOLDOWN ?>);
        } else {
            alert(data.message || "Failed to resend OTP.");
            startResendCooldown(<?= (int)OTP_REQUEST_COOLDOWN ?>);
        }
    } catch (error) {
        console.error(error);
        alert("Could not resend the OTP. Please try again.");
        startResendCooldown(<?= (int)OTP_REQUEST_COOLDOWN ?>);
    }
});
</script>


</body>
</html> 
