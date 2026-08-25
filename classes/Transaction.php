<?php
/**
* DeluxeSocial - Transaction Class (CLEAN PDO VERSION)
*/

require_once __DIR__ . '/../includes/db.php';

class Transaction {

    private $db;

    public function __construct() {
        $this->db = db(); // ✅ PDO connection
    }

    /* =========================
       CREATE TRANSACTION
    ========================= */
    public function createTransaction($user_id, $type, $amount, $reference, $payment_gateway = null) {

        $stmt = $this->db->prepare("
            INSERT INTO transactions
            (user_id, type, amount, reference, payment_gateway, status)
            VALUES (:user_id, :type, :amount, :reference, :payment_gateway, 'pending')
        ");

        $success = $stmt->execute([
            ':user_id'          => $user_id,
            ':type'             => $type,
            ':amount'           => $amount,
            ':reference'        => $reference,
            ':payment_gateway'  => $payment_gateway
        ]);

        return $success ? $this->db->lastInsertId() : false;
    }

    /* =========================
       UPDATE STATUS
    ========================= */
    public function updateTransactionStatus($reference, $status, $gateway_response = null) {

        $stmt = $this->db->prepare("
            UPDATE transactions
            SET status = :status,
                gateway_response = :gateway_response
            WHERE reference = :reference
        ");

        return $stmt->execute([
            ':status'            => $status,
            ':gateway_response'  => $gateway_response,
            ':reference'         => $reference
        ]);
    }

    /* =========================
       GET BY REFERENCE
    ========================= */
    public function getTransactionByRef($reference) {

        $stmt = $this->db->prepare("
            SELECT * FROM transactions
            WHERE reference = :reference
            LIMIT 1
        ");

        $stmt->execute([
            ':reference' => $reference
        ]);

        return $stmt->fetch();
    }

    /* =========================
       LOG WALLET ACTIVITY
    ========================= */
    public function logWalletActivity(
        $user_id,
        $transaction_id,
        $amount,
        $type,
        $description,
        $balance_after
    ) {

        $stmt = $this->db->prepare("
            INSERT INTO wallet_logs
            (user_id, transaction_id, amount, type, description, balance_after)
            VALUES
            (:user_id, :transaction_id, :amount, :type, :description, :balance_after)
        ");

        return $stmt->execute([
            ':user_id'         => $user_id,
            ':transaction_id'   => $transaction_id,
            ':amount'          => $amount,
            ':type'            => $type,
            ':description'     => $description,
            ':balance_after'   => $balance_after
        ]);
    }

    /* =========================
       GET USER TRANSACTIONS
    ========================= */
    public function getUserTransactions($user_id) {

        $stmt = $this->db->prepare("
            SELECT * FROM transactions
            WHERE user_id = :user_id
            ORDER BY transaction_date DESC
        ");

        $stmt->execute([
            ':user_id' => $user_id
        ]);

        return $stmt->fetchAll();
    }
} 
