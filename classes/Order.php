<?php
/**
* DeluxeSocial - Order Class (CLEAN PDO VERSION)
*/

require_once __DIR__ . '/../includes/db.php';

class Order {

    private $db;

    public function __construct() {
        $this->db = db(); // ✅ PDO connection
    }

    /* =========================
       CREATE ORDER
    ========================= */
    public function createOrder($user_id, $listing_id, $amount) {

        $stmt = $this->db->prepare("
            INSERT INTO orders
            (user_id, listing_id, amount, status)
            VALUES (:user_id, :listing_id, :amount, 'completed')
        ");

        $success = $stmt->execute([
            ':user_id'    => $user_id,
            ':listing_id' => $listing_id,
            ':amount'     => $amount
        ]);

        return $success ? $this->db->lastInsertId() : false;
    }

    /* =========================
       GET USER ORDERS
    ========================= */
    public function getUserOrders($user_id) {

        $stmt = $this->db->prepare("
            SELECT
                o.*,
                l.title,
                l.platform,
                l.account_username,
                l.account_password,
                l.account_email
            FROM orders o
            JOIN listings l ON o.listing_id = l.id
            WHERE o.user_id = :user_id
            ORDER BY o.order_date DESC
        ");

        $stmt->execute([
            ':user_id' => $user_id
        ]);

        return $stmt->fetchAll();
    }

    /* =========================
       GET ORDER BY ID
    ========================= */
    public function getOrderById($id) {

        $stmt = $this->db->prepare("
            SELECT
                o.*,
                l.title,
                l.platform,
                l.account_username,
                l.account_password,
                l.account_email
            FROM orders o
            JOIN listings l ON o.listing_id = l.id
            WHERE o.id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }
} 
