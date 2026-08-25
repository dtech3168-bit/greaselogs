<?php
/**
 * DeluxeSocial - Coupon Class
 */

require_once __DIR__ . '/../includes/db.php';

class Coupon {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Validate a coupon code
     */
    public function validateCoupon($code, $amount) {
        $this->db->query("SELECT * FROM coupons WHERE code = :code AND (expires_at IS NULL OR expires_at > NOW()) AND (usage_limit IS NULL OR used_count < usage_limit)");
        $this->db->bind(':code', $code);
        $coupon = $this->db->single();

        if (!$coupon) return false;
        if ($amount < $coupon['min_purchase_amount']) return false;

        return $coupon;
    }

    /**
     * Calculate discount
     */
    public function calculateDiscount($coupon, $amount) {
        if ($coupon['discount_type'] == 'percentage') {
            return ($coupon['discount_value'] / 100) * $amount;
        } else {
            return min($coupon['discount_value'], $amount);
        }
    }

    /**
     * Increment coupon usage
     */
    public function incrementUsage($id) {
        $this->db->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
?>
