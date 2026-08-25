<?php
/**
* DeluxeSocial - User Class (CLEAN PDO VERSION)
*/

require_once __DIR__ . '/../includes/db.php';

class User {

    private $db;

    public function __construct() {
        $this->db = db(); // ✅ PDO connection
    }

    /* =========================
       REGISTER USER
    ========================= */
    public function register($username, $email, $password) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password)
            VALUES (:username, :email, :password)
        ");

        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $hashed_password
        ]) ? $this->db->lastInsertId() : false;
    }

    /* =========================
       LOGIN USER
    ========================= */
    public function login($email, $password) {

        $stmt = $this->db->prepare("
            SELECT * FROM users WHERE email = :email LIMIT 1
        ");

        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /* =========================
       GET USER BY ID
    ========================= */
    public function getUserById($id) {

        $stmt = $this->db->prepare("
            SELECT * FROM users WHERE id = :id LIMIT 1
        ");

        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    /* =========================
       UPDATE WALLET BALANCE
    ========================= */
    public function updateWalletBalance($user_id, $amount, $type = 'credit') {

        $user = $this->getUserById($user_id);
        if (!$user) return false;

        $new_balance = ($type === 'credit')
            ? $user['wallet_balance'] + $amount
            : $user['wallet_balance'] - $amount;

        if ($new_balance < 0) return false;

        $stmt = $this->db->prepare("
            UPDATE users
            SET wallet_balance = :balance
            WHERE id = :id
        ");

        return $stmt->execute([
            ':balance' => $new_balance,
            ':id'      => $user_id
        ]) ? $new_balance : false;
    }

    /* =========================
       EMAIL ACTIVATION
    ========================= */
    public function createActivationToken($user_id) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiry = date('Y-m-d H:i:s', time() + ACTIVATION_EXPIRY);

        $stmt = $this->db->prepare("
            UPDATE users
            SET activation_token_hash = :token_hash,
                activation_expires_at = :expires_at
            WHERE id = :id
        ");

        $stmt->execute([
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiry,
            ':id' => $user_id
        ]);

        return $token;
    }

    public function activateByToken($token) {
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $tokenHash = hash('sha256', $token);

        $stmt = $this->db->prepare("
            SELECT id
            FROM users
            WHERE activation_token_hash = :token_hash
              AND activation_expires_at > NOW()
              AND email_verified_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':token_hash' => $tokenHash]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET email_verified_at = NOW(),
                activation_token_hash = NULL,
                activation_expires_at = NULL
            WHERE id = :id
        ");

        return $stmt->execute([':id' => $user['id']]);
    }

    /* =========================
       SET OTP
    ========================= */
    public function setOTP($user_id, $otp) {

        $expiry = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

        $stmt = $this->db->prepare("
            UPDATE users
            SET otp_code = :otp,
                otp_expires_at = :expiry
            WHERE id = :id
        ");

        return $stmt->execute([
            ':otp'    => $otp,
            ':expiry' => $expiry,
            ':id'     => $user_id
        ]);
    }

    /* =========================
       VERIFY OTP
    ========================= */
    public function verifyOTP($user_id, $otp) {

        $stmt = $this->db->prepare("
            SELECT id FROM users
            WHERE id = :id
              AND otp_code = :otp
              AND otp_expires_at > NOW()
            LIMIT 1
        ");

        $stmt->execute([
            ':id'  => $user_id,
            ':otp' => $otp
        ]);

        $user = $stmt->fetch();

        if ($user) {

            $stmt = $this->db->prepare("
                UPDATE users
                SET email_verified_at = NOW(),
                    otp_code = NULL,
                    otp_expires_at = NULL
                WHERE id = :id
            ");

            $stmt->execute([':id' => $user_id]);

            return true;
        }

        return false;
    }
} 
